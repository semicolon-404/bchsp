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
 * Shown at /ita/{year}. The items of a year are much the same from one year to
 * the next, so a new year can be started from the headings of the one before.
 */
class ItaItem extends Model
{
    /** @use HasFactory<ItaItemFactory> */
    use HasFactory;

    /** How many files one item can carry. */
    public const MAX_FILES = 20;

    private const YEARS_KEY = 'ita:years';

    protected $fillable = ['year', 'number', 'code', 'title', 'description', 'attachments', 'attachment_names', 'links', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_names' => 'array',
            'links' => 'array',
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
            Cache::forget(self::YEARS_KEY);
        });
        static::deleted(function () {
            Cache::forget(self::YEARS_KEY);
        });

        // The files belong to the item alone: one taken off it, or the whole item deleted, removes them.
        static::updated(function (ItaItem $item) {
            if ($item->wasChanged('attachments')) {
                $before = json_decode((string) $item->getRawOriginal('attachments'), true) ?: [];

                Storage::disk('public')->delete(array_values(array_diff($before, $item->attachments ?? [])));
            }
        });

        // Not an arrow function: delete() answers false for a file already gone, and a listener that answers false
        // stops the ones after it (the access log's among them).
        static::deleted(function (ItaItem $item) {
            Storage::disk('public')->delete($item->attachments ?? []);
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
        return Cache::rememberForever(self::YEARS_KEY, fn () => static::where('is_active', true)->distinct()->orderByDesc('year')->pluck('year')->map(fn ($year) => (int) $year)->all());
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
            static::create(['year' => $to, 'number' => $item->number, 'code' => $item->code, 'title' => $item->title, 'description' => $item->description]);
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
        $disk = Storage::disk('public');
        $evidence = [];

        foreach ($this->attachments ?? [] as $path) {
            if (is_string($path) && $disk->exists($path)) {
                $evidence[] = [
                    'name' => (string) ($this->attachment_names[$path] ?? basename($path)),
                    'url' => $disk->url($path),
                    'kind' => 'PDF · '.Number::fileSize($disk->size($path)),
                    'elsewhere' => false,
                ];
            }
        }

        foreach ($this->links ?? [] as $row) {
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
