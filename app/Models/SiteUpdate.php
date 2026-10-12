<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Database\Factories\SiteUpdateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A release of the website, at /updates: its number, the day it came out, and what
 * changed in it, each change marked as new, improved or fixed. One that is "planned"
 * is being worked on: it is shown apart, with no day promised. The footer of every
 * page names the newest release, so a visitor sees the site is looked after.
 */
class SiteUpdate extends Model
{
    /** @use HasFactory<SiteUpdateFactory> */
    use HasFactory, InTwoLanguages;

    public const STATUS_RELEASED = 'released';

    public const STATUS_PLANNED = 'planned';

    /** The kind of a change => [what the site calls it, the site's colour it is drawn in (resources/css/app.css)]. */
    public const KINDS = [
        'new' => ['New', 'var(--color-syn-function)'],
        'improved' => ['Improved', 'var(--color-brand)'],
        'fixed' => ['Fixed', 'var(--color-syn-variable)'],
    ];

    /** How many changes one release can list. */
    public const MAX_ITEMS = 30;

    private const NEWEST_KEY = 'site-updates:newest';

    protected $fillable = ['version', 'title', 'title_en', 'items', 'status', 'released_on'];

    protected $attributes = ['status' => self::STATUS_RELEASED];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'released_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // A release with no day came out today.
        static::saving(function (SiteUpdate $update) {
            if ($update->status === self::STATUS_RELEASED) {
                $update->released_on ??= now()->toDateString();
            }
        });

        // The footer of every page reads the newest one from the cache.
        static::saved(fn () => Cache::memo()->forget(self::NEWEST_KEY));
        static::deleted(fn () => Cache::memo()->forget(self::NEWEST_KEY));
    }

    /** Out, newest first. */
    public function scopeReleased(Builder $query): void
    {
        // By the day alone: the tests' SQLite keeps a time with it, which a plain comparison would trip over.
        $query->where('status', self::STATUS_RELEASED)->whereDate('released_on', '<=', now()->toDateString())->orderByDesc('released_on')->orderByDesc('id');
    }

    /** Being worked on, in the order they were written down. */
    public function scopePlanned(Builder $query): void
    {
        $query->where('status', self::STATUS_PLANNED)->orderBy('id');
    }

    /** The newest release, for the footer of every page; null when there is none. */
    public static function newest(): ?SiteUpdate
    {
        return Cache::memo()->rememberForever(self::NEWEST_KEY, fn () => static::released()->first() ?? false) ?: null;
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    /**
     * What changed, in the reader's language, in the order new, improved, fixed: only lines with something written.
     *
     * @return list<array{kind: string, label: string, colour: string, text: string}>
     */
    public function changes(): array
    {
        $changes = [];

        foreach ($this->items ?? [] as $item) {
            $kind = is_array($item) && isset(self::KINDS[$item['kind'] ?? null]) ? $item['kind'] : null;
            $english = app()->getLocale() === 'en' ? trim((string) ($item['text_en'] ?? '')) : '';
            $text = $english !== '' ? $english : trim((string) (is_array($item) ? ($item['text'] ?? '') : ''));

            if ($kind !== null && $text !== '') {
                $changes[] = ['kind' => $kind, 'label' => self::KINDS[$kind][0], 'colour' => self::KINDS[$kind][1], 'text' => $text];
            }
        }

        $order = array_flip(array_keys(self::KINDS));
        usort($changes, fn (array $a, array $b) => $order[$a['kind']] <=> $order[$b['kind']]);

        return $changes;
    }

    /** The day as a reader in Thailand writes it: 12 ต.ค. 2569, or 12 Oct 2026 in English. Nothing for one still planned. */
    public function releasedOn(): ?string
    {
        return $this->released_on === null ? null : (app()->getLocale() === 'th'
            ? $this->released_on->locale('th')->translatedFormat('j M ').($this->released_on->year + 543)
            : $this->released_on->locale('en')->translatedFormat('j M Y'));
    }
}
