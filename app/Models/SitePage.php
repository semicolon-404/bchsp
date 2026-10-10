<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Database\Factories\SitePageFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A page of text written in the panel (System > Pages): the hospital's
 * history, its vision and mission, how to get there. Thai is always there;
 * English is optional, and a reader in English gets the Thai where no English
 * was written.
 */
class SitePage extends Model
{
    /** @use HasFactory<SitePageFactory> */
    use HasFactory, InTwoLanguages;

    /** Lowercase words joined by hyphens: "about", "vision-mission". */
    public const SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const FOOTER_KEY = 'site-pages:footer';

    protected $fillable = ['slug', 'title', 'title_en', 'body', 'body_en', 'is_published', 'show_in_footer', 'position'];

    protected $attributes = ['is_published' => true, 'show_in_footer' => true, 'position' => 0];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The footer of every page lists these, so the list is kept until one changes.
        static::saved(fn () => Cache::forget(self::FOOTER_KEY));
        static::deleted(fn () => Cache::forget(self::FOOTER_KEY));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * What the footer links to: published pages that asked to be there, in the administrator's order.
     *
     * @return Collection<int, SitePage>
     */
    public static function footerLinks(): Collection
    {
        return Cache::rememberForever(self::FOOTER_KEY, fn () => static::published()
            ->where('show_in_footer', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'slug', 'title', 'title_en']));
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    /** The start of the page, for search engines and link previews. */
    public function summary(): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags($this->inLanguage('body')))), 160);
    }

    /** The text as HTML that is safe to print: Filament's renderer drops anything that is not formatting. */
    public function renderedBody(): string
    {
        return RichContentRenderer::make($this->inLanguage('body'))->fileAttachmentsDisk('public')->toHtml();
    }
}
