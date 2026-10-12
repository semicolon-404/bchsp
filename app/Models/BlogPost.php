<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\BlogPostFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An article at /blog/{slug}: health knowledge, or news of what the hospital
 * has been doing. Written in the panel by staff, public to everyone including
 * search engines.
 */
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory, InTwoLanguages;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'author_id', 'blog_category_id', 'department_id',
        'title', 'title_en', 'slug', 'excerpt', 'excerpt_en', 'body', 'body_en',
        'cover_path', 'gallery', 'status', 'published_at',
    ];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'gallery' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post) {
            $post->slug = Slug::unique($post, $post->slug ?: $post->title, 'article');

            // Publishing without a date means "now".
            if ($post->status === self::STATUS_PUBLISHED && ! $post->published_at) {
                $post->published_at = now();
            }
        });

        // The cover belongs to the article alone, so it goes when the article does or when it is replaced.
        static::updated(function (BlogPost $post) {
            if ($post->wasChanged('cover_path') && $post->getOriginal('cover_path')) {
                Storage::disk('public')->delete($post->getOriginal('cover_path'));
            }

            // So do the photographs taken out of its album.
            if ($post->wasChanged('gallery')) {
                $before = json_decode((string) $post->getRawOriginal('gallery'), true) ?: [];

                Storage::disk('public')->delete(array_values(array_diff($before, $post->gallery ?? [])));
            }
        });

        static::deleted(function (BlogPost $post) {
            if ($post->cover_path) {
                Storage::disk('public')->delete($post->cover_path);
            }

            Storage::disk('public')->delete($post->gallery ?? []);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** The unit of the hospital that wrote it, when one did: it is then on that unit's board. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /** Visible to the public: published, and not scheduled for the future. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && (bool) $this->published_at?->isPast();
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status === self::STATUS_DRAFT => 'Draft',
            (bool) $this->published_at?->isFuture() => 'Scheduled',
            default => 'Published',
        };
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    /** The summary in the reader's language, or the start of the article when none was written. */
    public function localExcerpt(): string
    {
        return $this->inLanguage('excerpt') ?: Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags($this->inLanguage('body')))), 160);
    }

    /** The article as HTML that is safe to print: Filament's renderer drops anything that is not formatting. */
    public function renderedBody(): string
    {
        return RichContentRenderer::make($this->inLanguage('body'))->fileAttachmentsDisk('public')->toHtml();
    }

    /** How many photographs an album can hold. */
    public const MAX_PHOTOS = 40;

    /**
     * The addresses of the photographs in its album, in the order staff gave them.
     *
     * @return list<string>
     */
    public function photos(): array
    {
        return array_map(fn (string $path) => Storage::disk('public')->url($path), array_values(array_filter($this->gallery ?? [], 'is_string')));
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /** The date as a reader in Thailand writes it: 10 ตุลาคม 2569, or 10 October 2026 in English. */
    public function publishedOn(): string
    {
        $date = $this->published_at ?? $this->created_at;

        return app()->getLocale() === 'th'
            ? $date->locale('th')->translatedFormat('j F ').($date->year + 543)
            : $date->locale('en')->translatedFormat('j F Y');
    }
}
