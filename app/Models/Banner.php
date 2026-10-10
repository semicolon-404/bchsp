<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A picture with an optional link, put on the home page by staff for a while:
 * a wide banner across the top, or a poster (a campaign, a clinic's new hours)
 * in the grid of posters. Each runs between two dates, so one can be prepared
 * ahead and takes itself down afterwards. One marked "opens over the page" is
 * also shown in a window when the site is first opened.
 */
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    /** Where it goes => what the panel calls it. */
    public const PLACES = [
        'banner' => 'Slide at the top of the home page',
        'poster' => 'Poster in the grid of posters',
    ];

    /** More than this at once and nobody reaches the last one. */
    public const SHOWN_AT_ONCE = ['banner' => 8, 'poster' => 8];

    protected $fillable = ['title', 'image_path', 'url', 'placement', 'is_popup', 'starts_at', 'ends_at', 'is_active', 'position'];

    protected $attributes = ['placement' => 'banner', 'is_popup' => false, 'is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return [
            'is_popup' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Banner $banner) => $banner->starts_at ??= now());

        // The picture belongs to the banner alone, so it goes when the banner does or when it is replaced.
        static::updated(function (Banner $banner) {
            if ($banner->wasChanged('image_path') && $banner->getOriginal('image_path')) {
                Storage::disk('public')->delete($banner->getOriginal('image_path'));
            }
        });

        // Not an arrow function: delete() answers false for a file already gone, and a listener that answers false
        // stops the ones after it (the access log's among them).
        static::deleted(function (Banner $banner) {
            Storage::disk('public')->delete($banner->image_path);
        });
    }

    /** Switched on and inside its dates, in the order staff gave them. */
    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('position')
            ->orderByDesc('starts_at');
    }

    /**
     * What a place on the home page shows right now.
     *
     * @return Collection<int, Banner>
     */
    public static function showingAs(string $placement): Collection
    {
        return static::current()->where('placement', $placement)->limit(self::SHOWN_AT_ONCE[$placement])->get();
    }

    /**
     * The slides of the home page: the ones staff put up as slides, then the pictures of announcements that asked
     * to be among them. An announcement's slide leads to the announcement, and goes when the announcement ends.
     *
     * @return Collection<int, Banner>
     */
    public static function slides(): Collection
    {
        $slides = static::showingAs('banner');

        Announcement::current()->where('in_slider', true)->whereNotNull('image_path')->inListOrder()->limit(self::SHOWN_AT_ONCE['banner'])->get()
            ->each(fn (Announcement $announcement) => $slides->push(new static([
                'title' => $announcement->localTitle(),
                'image_path' => $announcement->image_path,
                'url' => route('announcements.show', $announcement),
            ])));

        return $slides->take(self::SHOWN_AT_ONCE['banner'])->values();
    }

    /** The one that opens over the page: the first of those marked for it. One at a time: two windows in a row are a nuisance. */
    public static function popup(): ?Banner
    {
        return static::current()->where('is_popup', true)->first();
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /** A link to somewhere else opens in a new tab. */
    public function leavesTheSite(): bool
    {
        return $this->url !== null && parse_url($this->url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST);
    }

    /** What the panel's list says about it. */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'Off',
            (bool) $this->starts_at?->isFuture() => 'Scheduled',
            (bool) $this->ends_at?->isPast() => 'Ended',
            default => 'Showing',
        };
    }
}
