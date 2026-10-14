<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A group of work (กลุ่มงาน) of the hospital, as its organisation chart has it: who heads it, how many work
 * in it, and the units of work inside it. Shown on "Organisation structure" and "Executives" under About us
 * (App\Support\Organisation). A department of the site is another thing: a place a patient looks for, with a
 * profile and a board; a group points at one when one stands for it.
 */
class WorkGroup extends Model
{
    use HasFactory, InTwoLanguages;

    protected $fillable = ['name', 'name_en', 'head_id', 'head_name', 'head_position', 'head_photo_path', 'staff_count', 'units', 'department_id', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['units' => 'array', 'is_active' => 'boolean', 'staff_count' => 'integer'];
    }

    protected static function booted(): void
    {
        // The head's photograph belongs to the group alone.
        static::updated(function (WorkGroup $group) {
            if ($group->wasChanged('head_photo_path') && $group->getOriginal('head_photo_path')) {
                Storage::disk('public')->delete($group->getOriginal('head_photo_path'));
            }
        });

        static::deleted(fn (WorkGroup $group) => $group->head_photo_path && Storage::disk('public')->delete($group->head_photo_path));
    }

    /**
     * Who heads it, from the register of people (App\Models\Person). A group written before there was a
     * register may have only a name of its own (head_name): the three methods below read whichever there is.
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'head_id');
    }

    public function headName(): ?string
    {
        return trim((string) ($this->head?->name ?? $this->head_name)) ?: null;
    }

    public function headPosition(): ?string
    {
        return trim((string) ($this->head ? $this->head->position : $this->head_position)) ?: null;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function localName(): string
    {
        return $this->inLanguage('name');
    }

    /**
     * The units of work inside it, blank lines dropped.
     *
     * @return list<string>
     */
    public function unitList(): array
    {
        return array_values(array_filter(array_map(fn ($unit) => trim((string) $unit), $this->units ?? []), fn (string $unit) => $unit !== ''));
    }

    public function headPhotoUrl(): ?string
    {
        if ($this->head) {
            return $this->head->photoUrl();
        }

        return $this->head_photo_path ? Storage::disk('public')->url($this->head_photo_path) : null;
    }
}
