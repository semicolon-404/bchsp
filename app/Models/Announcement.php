<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\AnnouncementFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Something the hospital has to tell the public, at /announcements/{slug}:
 * a notice, a procurement result, a job opening. It may carry PDF files and be
 * pinned to the top of the list. It has two dates: one prepared ahead comes out
 * by itself on the first; after the second it leaves the home page and the top
 * of the list, but stays in the archive, where it is found by its fiscal year.
 * (A procurement notice has to stay readable long after it stopped being news.)
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, InTwoLanguages;

    /** How many files one announcement can carry. */
    public const MAX_FILES = 10;

    protected $fillable = [
        'announcement_type_id', 'author_id', 'department_id',
        'title', 'title_en', 'slug', 'body', 'body_en',
        'image_path', 'in_slider', 'attachments', 'attachment_names', 'links', 'is_pinned', 'starts_at', 'ends_at',
    ];

    protected $attributes = ['is_pinned' => false, 'in_slider' => false];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_names' => 'array',
            'links' => 'array',
            'is_pinned' => 'boolean',
            'in_slider' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement) {
            $announcement->slug = Slug::unique($announcement, $announcement->slug ?: $announcement->title, 'announcement');
            $announcement->starts_at ??= now();
        });

        // The files belong to the announcement alone: one taken off it, or the whole announcement deleted, removes them.
        static::updated(function (Announcement $announcement) {
            if ($announcement->wasChanged('attachments')) {
                $before = json_decode((string) $announcement->getRawOriginal('attachments'), true) ?: [];

                Storage::disk('public')->delete(array_values(array_diff($before, $announcement->attachments ?? [])));
            }

            // So does its picture.
            if ($announcement->wasChanged('image_path') && $announcement->getOriginal('image_path')) {
                Storage::disk('public')->delete($announcement->getOriginal('image_path'));
            }
        });

        // Not an arrow function: delete() answers false for a file already gone, and a listener that answers false
        // stops the ones after it (the access log's among them).
        static::deleted(function (Announcement $announcement) {
            Storage::disk('public')->delete(array_filter([...($announcement->attachments ?? []), $announcement->image_path]));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AnnouncementType::class, 'announcement_type_id');
    }

    /** The unit of the hospital that put it out, when one did: it is then on that unit's board. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Out: its first date has come. Everything the public can read, the archive included. */
    public function scopePublished(Builder $query): void
    {
        $query->where('starts_at', '<=', now());
    }

    /** Of a fiscal year, as written in Thailand (2569): from 1 October of the year before to 30 September. */
    public function scopeOfFiscalYear(Builder $query, int $year): void
    {
        [$from, $until] = self::fiscalYearDates($year);

        $query->where('starts_at', '>=', $from)->where('starts_at', '<', $until);
    }

    /**
     * The first moment of a fiscal year and the first moment of the next.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function fiscalYearDates(int $year): array
    {
        $from = Carbon::create($year - 543 - 1, 10, 1, 0, 0, 0, config('app.timezone'));

        return [$from, $from->copy()->addYear()];
    }

    /** The fiscal year a date falls in, as written in Thailand: October 2025 is in 2569. */
    public static function fiscalYearOf(Carbon $date): int
    {
        return $date->year + 543 + ($date->month >= 10 ? 1 : 0);
    }

    /**
     * The fiscal years that have something out, newest first, with how many announcements each holds.
     * Counted here, not by the database: MariaDB and the tests' SQLite do not share a way to take a year from a date.
     *
     * @return array<int, int> [2569 => 48, 2568 => 112]
     */
    public static function fiscalYears(?int $typeId = null): array
    {
        $years = [];

        static::published()->when($typeId, fn (Builder $query) => $query->where('announcement_type_id', $typeId))->pluck('starts_at')
            ->each(function ($date) use (&$years) {
                $year = self::fiscalYearOf($date);
                $years[$year] = ($years[$year] ?? 0) + 1;
            });

        krsort($years);

        return $years;
    }

    /** Inside its dates right now: what the home page shows. */
    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** The order of every list: pinned ones that have not ended first, then the newest. */
    public function scopeInListOrder(Builder $query): void
    {
        $query
            ->orderByRaw('case when is_pinned = 1 and (ends_at is null or ends_at > ?) then 0 else 1 end', [now()])
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    /** Out, whether or not it has ended. */
    public function isPublished(): bool
    {
        return (bool) $this->starts_at?->isPast();
    }

    /** Still pinned to the top: pinned, and not ended. */
    public function isPinnedNow(): bool
    {
        return $this->is_pinned && ! $this->ends_at?->isPast();
    }

    public function isCurrent(): bool
    {
        return (bool) $this->starts_at?->isPast() && ! $this->ends_at?->isPast();
    }

    /** What the panel's list says about it. */
    public function state(): string
    {
        return match (true) {
            (bool) $this->starts_at?->isFuture() => 'Scheduled',
            (bool) $this->ends_at?->isPast() => 'In the archive',
            default => 'Showing',
        };
    }

    /** The address of its "one page" picture, or null when it has none. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    public function hasBody(): bool
    {
        return filled(trim(strip_tags($this->inLanguage('body'))));
    }

    /** The text as HTML that is safe to print: Filament's renderer drops anything that is not formatting. */
    public function renderedBody(): string
    {
        return RichContentRenderer::make($this->inLanguage('body'))->fileAttachmentsDisk('public')->toHtml();
    }

    /**
     * The attached files that are really there, each with the name it was uploaded under.
     *
     * @return list<array{name: string, url: string, size: string}>
     */
    public function files(): array
    {
        $disk = Storage::disk('public');
        $files = [];

        foreach ($this->attachments ?? [] as $path) {
            if (is_string($path) && $disk->exists($path)) {
                $files[] = [
                    'name' => (string) ($this->attachment_names[$path] ?? basename($path)),
                    'url' => $disk->url($path),
                    'size' => Number::fileSize($disk->size($path)),
                ];
            }
        }

        return $files;
    }

    /**
     * The documents kept elsewhere (Google Drive) and linked from the announcement: only web addresses
     * over HTTPS, each with something to call it.
     *
     * @return list<array{label: string, url: string, host: string}>
     */
    public function documentLinks(): array
    {
        $links = [];

        foreach ($this->links ?? [] as $row) {
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

            if (preg_match('~^https://[^\s"\'<>]+$~', $url)) {
                $host = (string) parse_url($url, PHP_URL_HOST);

                $links[] = ['label' => trim((string) ($row['label'] ?? '')) ?: $host, 'url' => $url, 'host' => $host];
            }
        }

        return $links;
    }

    /** Whether there is anything to open: a file kept here, or a link to one kept elsewhere. */
    public function hasDocuments(): bool
    {
        return filled($this->attachments) || $this->documentLinks() !== [];
    }

    /** The date as a reader in Thailand writes it: 10 ต.ค. 2569, or 10 Oct 2026 in English. */
    public function shownOn(): string
    {
        $date = $this->starts_at ?? $this->created_at;

        return app()->getLocale() === 'th'
            ? $date->locale('th')->translatedFormat('j M ').($date->year + 543)
            : $date->locale('en')->translatedFormat('j M Y');
    }
}
