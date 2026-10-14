<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Someone the site names: kept once in the register (panel: Hospital > Personnel), with a position and a
 * photograph, and named from wherever they appear, as the head of a group of work or as the director. So a
 * photograph is given once and a name is corrected in one place.
 *
 * Not an account: nobody signs in as one of these (that is App\Models\User).
 */
class Person extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position', 'photo_path', 'work_group_id', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // The photograph belongs to the person alone: one replaced, or the person removed, takes it away.
        static::updated(function (Person $person) {
            if ($person->wasChanged('photo_path') && $person->getOriginal('photo_path')) {
                Storage::disk('public')->delete($person->getOriginal('photo_path'));
            }
        });

        static::deleted(fn (Person $person) => $person->photo_path && Storage::disk('public')->delete($person->photo_path));
    }

    /** The group of work they belong to. */
    public function workGroup(): BelongsTo
    {
        return $this->belongsTo(WorkGroup::class);
    }

    /** The groups of work they head. */
    public function heads(): HasMany
    {
        return $this->hasMany(WorkGroup::class, 'head_id');
    }

    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
