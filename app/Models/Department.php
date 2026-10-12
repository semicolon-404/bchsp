<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A unit of the hospital, at /departments/{slug}: what it does, where it is,
 * its phone number and its hours, and under them its board: the posters
 * and announcements it has put out. One that is switched off is kept in the
 * panel and hidden from the site.
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, InTwoLanguages;

    protected $fillable = [
        'name', 'name_en', 'slug', 'description', 'description_en',
        'location', 'phone', 'hours', 'image_path', 'is_active', 'sort_order',
    ];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            $department->slug = Slug::unique($department, $department->slug ?: $department->name, 'department');
        });

        // The picture belongs to the department alone, so it goes when the department does or when it is replaced.
        static::updated(function (Department $department) {
            if ($department->wasChanged('image_path') && $department->getOriginal('image_path')) {
                Storage::disk('public')->delete($department->getOriginal('image_path'));
            }
        });

        static::deleted(function (Department $department) {
            if ($department->image_path) {
                Storage::disk('public')->delete($department->image_path);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** What the unit has put out: the unit's board on its page. */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /** Shown on the site, in the administrator's order. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function localName(): string
    {
        return $this->inLanguage('name');
    }

    public function localDescription(): string
    {
        return $this->inLanguage('description');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** The number as a link a phone can dial: the first one, when two are written ("044-579076 ต่อ 101"). */
    public function dialUrl(): ?string
    {
        if (blank($this->phone)) {
            return null;
        }

        preg_match('/[0-9+][0-9 \-]{4,}/', $this->phone, $found);
        $digits = preg_replace('/[^0-9+]/', '', $found[0] ?? '');

        return $digits !== '' ? 'tel:'.$digits : null;
    }
}
