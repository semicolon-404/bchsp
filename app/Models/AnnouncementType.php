<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\AnnouncementTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** What kind of announcement it is: news, procurement, job openings. Shown as a filter above the list. */
class AnnouncementType extends Model
{
    /** @use HasFactory<AnnouncementTypeFactory> */
    use HasFactory, InTwoLanguages;

    protected $fillable = ['name', 'name_en', 'slug', 'sort_order'];

    protected $attributes = ['sort_order' => 0];

    protected static function booted(): void
    {
        static::saving(function (AnnouncementType $type) {
            $type->slug = Slug::unique($type, $type->slug ?: $type->name, 'type');
        });
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /** The name in the reader's language. */
    public function localName(): string
    {
        return $this->inLanguage('name');
    }
}
