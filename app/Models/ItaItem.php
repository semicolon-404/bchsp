<?php

namespace App\Models;

use Database\Factories\ItaItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * One numbered item of a fiscal year's Integrity and Transparency Assessment
 * ("MOIT 1" of 2569), with the evidence the hospital publishes under it: PDF
 * files kept here, or links to documents kept elsewhere (Google Drive).
 *
 * An item sits under a heading of the assessment (category: "1. การเปิดเผยข้อมูล")
 * and has parts: the numbered documents asked for under it, each due in a round
 * (ROUNDS) and with evidence of its own. An item with no parts carries its
 * evidence itself.
 *
 * Shown at /ita/{year}. The items of a year are much the same from one year to
 * the next, so a new year can be started from the headings of the one before.
 */
class ItaItem extends Model
{
    /** @use HasFactory<ItaItemFactory> */
    use HasFactory;

    /** How many files one item can carry. */
    public const MAX_FILES = 20;

    /** When a part is due => what it is called. The assessment looks twice a year: after six months and after twelve. */
    public const ROUNDS = [
        'year' => 'The whole year',
        'q2' => 'Quarter 2 · six-month round',
        'q4' => 'Quarter 4 · twelve-month round',
    ];

    private const YEARS_KEY = 'ita:years';

    protected $fillable = ['year', 'number', 'code', 'category', 'title', 'description', 'attachments', 'attachment_names', 'links', 'parts', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_names' => 'array',
            'links' => 'array',
            'parts' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ItaItem $item) {
            // Called "MOIT 3" unless staff wrote something else.
            $item->code = filled($item->code) ? $item->code : 'MOIT '.$item->number;
        });

        // The menu of every page lists the years, so the list is kept until an item changes.
        // Written out, not as arrow functions: forget() answers false when there was nothing to forget,
        // and a listener that answers false stops the ones after it (the removal of the files, below).
        static::saved(function () {
            Cache::memo()->forget(self::YEARS_KEY);
        });
        static::deleted(function () {
            Cache::memo()->forget(self::YEARS_KEY);
        });

        // The files belong to the item alone: one taken off it, or the whole item deleted, removes them.
        static::updated(function (ItaItem $item) {
            if ($item->wasChanged('attachments') || $item->wasChanged('parts')) {
                $before = self::filesIn(json_decode((string) $item->getRawOriginal('attachments'), true) ?: [], json_decode((string) $item->getRawOriginal('parts'), true) ?: []);

                Storage::disk('public')->delete(array_values(array_diff($before, self::filesIn($item->attachments ?? [], $item->parts ?? []))));
            }
        });

        // Not an arrow function: delete() answers false for a file already gone, and a listener that answers false
        // stops the ones after it (the access log's among them).
        static::deleted(function (ItaItem $item) {
            Storage::disk('public')->delete(self::filesIn($item->attachments ?? [], $item->parts ?? []));
        });
    }

    /** Shown on the site, in the order of the year's list. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('number')->orderBy('id');
    }

    /**
     * The fiscal years that have something to show, newest first.
     *
     * @return list<int>
     */
    public static function years(): array
    {
        return Cache::memo()->rememberForever(self::YEARS_KEY, fn () => static::where('is_active', true)->distinct()->orderByDesc('year')->pluck('year')->map(fn ($year) => (int) $year)->all());
    }

    /**
     * Start a year from the headings of another: the same numbers, codes and titles, with no evidence yet.
     * Adds nothing when the new year already has items.
     *
     * @return int how many items were made
     */
    public static function startYearFrom(int $from, int $to): int
    {
        if ($from === $to || static::where('year', $to)->exists()) {
            return 0;
        }

        $made = 0;

        foreach (static::where('year', $from)->orderBy('number')->get() as $item) {
            static::create([
                'year' => $to, 'number' => $item->number, 'code' => $item->code, 'category' => $item->category, 'title' => $item->title, 'description' => $item->description,
                // The same parts, without last year's evidence.
                'parts' => collect($item->parts ?? [])->map(fn (array $part) => ['round' => $part['round'] ?? 'year', 'title' => $part['title'] ?? ''])->all() ?: null,
            ]);
            $made++;
        }

        return $made;
    }

    /**
     * The evidence of this item, files first: each with a name, where it opens, and what it is.
     *
     * @return list<array{name: string, url: string, kind: string, elsewhere: bool}>
     */
    public function evidence(): array
    {
        return self::evidenceOf($this->attachments ?? [], $this->attachment_names ?? [], $this->links ?? []);
    }

    /**
     * The parts that are shown, in their order, each with its evidence; only those of one round when one is given.
     *
     * @return list<array{round: string, title: string, evidence: list<array{name: string, url: string, kind: string, elsewhere: bool}>}>
     */
    public function shownParts(?string $round = null): array
    {
        $parts = [];

        foreach ($this->parts ?? [] as $part) {
            $in = array_key_exists($part['round'] ?? '', self::ROUNDS) ? $part['round'] : 'year';

            if (blank($part['title'] ?? null) || ($round !== null && $in !== $round)) {
                continue;
            }

            $parts[] = ['round' => $in, 'title' => (string) $part['title'], 'evidence' => self::evidenceOf($part['attachments'] ?? [], $part['attachment_names'] ?? [], $part['links'] ?? [])];
        }

        return $parts;
    }

    /**
     * How far the item has got: of the things that need documents (its parts, or the item itself when it has
     * none), how many have them. Only those of one round when one is given.
     *
     * @return array{done: int, of: int}
     */
    public function progress(?string $round = null): array
    {
        if (blank($this->parts)) {
            return ['done' => $round === null || $round === 'year' ? (int) ($this->evidence() !== []) : 0, 'of' => $round === null || $round === 'year' ? 1 : 0];
        }

        $parts = $this->shownParts($round);

        return ['done' => count(array_filter($parts, fn (array $part) => $part['evidence'] !== [])), 'of' => count($parts)];
    }

    /** Every file kept for an item, its own and its parts'. */
    private static function filesIn(array $attachments, array $parts): array
    {
        foreach ($parts as $part) {
            $attachments = array_merge($attachments, is_array($part['attachments'] ?? null) ? $part['attachments'] : []);
        }

        return array_values(array_filter($attachments, 'is_string'));
    }

    /** @return list<array{name: string, url: string, kind: string, elsewhere: bool}> */
    private static function evidenceOf(array $attachments, array $names, array $links): array
    {
        $disk = Storage::disk('public');
        $evidence = [];

        foreach ($attachments as $path) {
            if (is_string($path) && $disk->exists($path)) {
                $evidence[] = [
                    'name' => (string) ($names[$path] ?? basename($path)),
                    'url' => $disk->url($path),
                    'kind' => 'PDF · '.Number::fileSize($disk->size($path)),
                    'elsewhere' => false,
                ];
            }
        }

        foreach ($links as $row) {
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

            // Only web addresses over HTTPS are ever put into a link.
            if (preg_match('~^https://[^\s"\'<>]+$~', $url)) {
                $host = (string) parse_url($url, PHP_URL_HOST);

                $evidence[] = ['name' => trim((string) ($row['label'] ?? '')) ?: $host, 'url' => $url, 'kind' => $host, 'elsewhere' => true];
            }
        }

        return $evidence;
    }
}
