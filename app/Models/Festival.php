<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A festival the home page dresses up for, between two days (Festivals in the panel): Loy Krathong's river with the
 * departments' krathongs, Christmas snow, New Year fireworks, Valentine's hearts, Songkran drops. While one is on, it
 * takes the place of the ambulance at the foot of the first screen. Outside its days nothing of it is loaded.
 */
class Festival extends Model
{
    /** Theme => what the panel calls it. */
    public const THEMES = [
        'loykrathong' => 'Loy Krathong',
        'christmas' => 'Christmas',
        'newyear' => 'New Year',
        'valentine' => 'Valentine\'s Day',
        'songkran' => 'Songkran',
    ];

    /** How much moves on the page: few, some (as it starts) or many. */
    public const INTENSITIES = ['low' => 'A little', 'medium' => 'Some', 'high' => 'A lot'];

    protected $fillable = ['name', 'theme', 'starts_on', 'ends_on', 'intensity', 'is_active'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    public function krathongs(): HasMany
    {
        return $this->hasMany(Krathong::class)->latest();
    }

    public function scopeOnToday(Builder $query): void
    {
        $query->where('is_active', true)->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today());
    }

    /** The festival on today, if any: one query a request (the first to start wins where two overlap). */
    public static function today(): ?self
    {
        $attributes = request()->attributes;

        if (! $attributes->has('festival.today')) {
            $attributes->set('festival.today', static::onToday()->orderBy('starts_on')->first());
        }

        return $attributes->get('festival.today');
    }

    /** How many things float or fall at once, by the intensity chosen. */
    public function pieces(): int
    {
        return ['low' => 6, 'medium' => 10, 'high' => 16][$this->intensity] ?? 10;
    }
}
