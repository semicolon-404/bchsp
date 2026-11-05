<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A festival or day of note the home page dresses up for, between two days (Festivals in the panel): Loy Krathong's
 * river with the krathongs, and for the rest a few things falling or rising in the day's colours: petals for the
 * royal and Buddhist days, balloons for Children's Day, red and gold for Chinese New Year, snow, hearts, bursts of
 * light. A day of mourning moves nothing. Outside its days nothing of it is loaded.
 */
class Festival extends Model
{
    /** Theme => what the panel calls it. */
    public const THEMES = [
        'newyear' => 'New Year',
        'children' => 'Children\'s Day',
        'chinese' => 'Chinese New Year',
        'valentine' => 'Valentine\'s Day',
        'makha' => 'Makha Bucha',
        'chakri' => 'Chakri Day',
        'songkran' => 'Songkran',
        'visakha' => 'Visakha Bucha',
        'queen' => 'The Queen\'s birthday',
        'asalha' => 'Asalha Bucha and Buddhist Lent',
        'king' => 'The King\'s birthday',
        'mother' => 'Mother\'s Day',
        'mourning' => 'A day of remembrance',
        'nurses' => 'National Nurses\' Day',
        'chulalongkorn' => 'Chulalongkorn Day',
        'endlent' => 'End of Buddhist Lent',
        'loykrathong' => 'Loy Krathong',
        'father' => 'Father\'s Day',
        'constitution' => 'Constitution Day',
        'christmas' => 'Christmas',
    ];

    /**
     * How each looks: what falls or rises (petal, balloon, coin, snow, heart, drop, burst, confetti, or nothing) and
     * in which colours.
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    public const LOOKS = [
        'newyear' => ['burst', ['#fde047', '#f472b6', '#60a5fa']],
        'children' => ['balloon', ['#f43f5e', '#f59e0b', '#22c55e', '#3b82f6', '#a855f7']],
        'chinese' => ['coin', ['#dc2626', '#facc15']],
        'valentine' => ['heart', ['#f43f5e', '#fb7185']],
        'makha' => ['petal', ['#fde68a', '#fffbeb']],
        'chakri' => ['petal', ['#facc15', '#fde68a']],
        'songkran' => ['drop', ['#38bdf8', '#7dd3fc']],
        'visakha' => ['petal', ['#fde68a', '#fffbeb']],
        'queen' => ['petal', ['#a855f7', '#d8b4fe']],
        'asalha' => ['petal', ['#fbbf24', '#fef3c7']],
        'king' => ['petal', ['#facc15', '#fde047']],
        'mother' => ['petal', ['#3b82f6', '#bfdbfe']],
        'mourning' => ['none', []],
        'nurses' => ['heart', ['#ffffff', '#fbcfe8']],
        'chulalongkorn' => ['petal', ['#f472b6', '#fbcfe8']],
        'endlent' => ['petal', ['#fbbf24', '#fde68a']],
        'loykrathong' => ['river', []],
        'father' => ['petal', ['#facc15', '#fde047']],
        'constitution' => ['confetti', ['#dc2626', '#ffffff', '#1d4ed8']],
        'christmas' => ['snow', ['#ffffff', '#dbeafe']],
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

    /** What falls or rises, and its colours. */
    public function look(): array
    {
        return self::LOOKS[$this->theme] ?? ['petal', ['#fde68a']];
    }
}
