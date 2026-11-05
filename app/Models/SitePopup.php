<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A popup set up on its own in the panel (Popups), apart from the announcements: a picture, a title, a few words,
 * up to two buttons to anywhere; its size, its place (the middle of the screen, or a card in the corner that does
 * not cover the page), its colour, on the home page or every page, how often a visitor sees it, and its days.
 * Shown by components/site/site-popups and resources/js/site-popup.js.
 */
class SitePopup extends Model
{
    public const SIZES = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];

    public const PLACEMENTS = ['center' => 'In the middle of the screen', 'corner' => 'A card in the lower corner'];

    public const PAGES = ['home' => 'The home page', 'all' => 'Every page'];

    public const FREQUENCIES = ['once' => 'Once, until it is changed', 'daily' => 'Once a day', 'always' => 'Every visit'];

    protected $fillable = ['title', 'body', 'image_path', 'button_label', 'button_url', 'second_label', 'second_url', 'size', 'placement', 'accent', 'pages', 'frequency', 'starts_at', 'ends_at', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function scopeShowing(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * Those showing on this page, in order: on the home page those for it and for every page, elsewhere those for
     * every page. One query a request.
     *
     * @return Collection<int, self>
     */
    public static function forPage(bool $home): Collection
    {
        return static::showing()->when(! $home, fn (Builder $query) => $query->where('pages', 'all'))
            ->orderBy('sort_order')->orderBy('id')->limit(5)->get();
    }

    /** Its text as set out in the panel, made safe to show (App\Support\RichText). */
    public function renderedBody(): string
    {
        return RichText::html($this->body);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** What the visitor's browser remembers it by: changed in the panel, it is new to them again. */
    public function seenKey(): string
    {
        return $this->id.'-'.$this->updated_at?->timestamp;
    }
}
