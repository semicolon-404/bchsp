<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\BlogCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** What an article is about: "Health knowledge", "Activities". Shown as a filter above the blog. */
class BlogCategory extends Model
{
    /** @use HasFactory<BlogCategoryFactory> */
    use HasFactory, InTwoLanguages;

    protected $fillable = ['name', 'name_en', 'slug', 'sort_order'];

    protected $attributes = ['sort_order' => 0];

    protected static function booted(): void
    {
        static::saving(function (BlogCategory $category) {
            $category->slug = Slug::unique($category, $category->slug ?: $category->name, 'category');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /** The name in the reader's language. */
    public function localName(): string
    {
        return $this->inLanguage('name');
    }
}
