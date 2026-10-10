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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Something the hospital has to tell the public, at /announcements/{slug}:
 * a notice, a procurement result, a job opening. It may carry PDF files, be
 * pinned to the top of the list, and is shown between two dates: one prepared
 * ahead comes out by itself, and one that has ended takes itself down.
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, InTwoLanguages;

    /** How many files one announcement can carry. */
    public const MAX_FILES = 10;

    protected $fillable = [
        'announcement_type_id', 'author_id',
        'title', 'title_en', 'slug', 'body', 'body_en',
        'attachments', 'attachment_names', 'is_pinned', 'starts_at', 'ends_at',
    ];

    protected $attributes = ['is_pinned' => false];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_names' => 'array',
            'is_pinned' => 'boolean',
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
        });

        static::deleted(fn (Announcement $announcement) => Storage::disk('public')->delete($announcement->attachments ?? []));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AnnouncementType::class, 'announcement_type_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Inside its dates right now. */
    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** The order of every list: pinned ones first, then the newest. */
    public function scopeInListOrder(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('starts_at')->orderByDesc('id');
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
            (bool) $this->ends_at?->isPast() => 'Ended',
            default => 'Showing',
        };
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

    /** The date as a reader in Thailand writes it: 10 ต.ค. 2569, or 10 Oct 2026 in English. */
    public function shownOn(): string
    {
        $date = $this->starts_at ?? $this->created_at;

        return app()->getLocale() === 'th'
            ? $date->locale('th')->translatedFormat('j M ').($date->year + 543)
            : $date->locale('en')->translatedFormat('j M Y');
    }
}
