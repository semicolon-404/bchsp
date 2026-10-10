<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Database\Factories\ClinicSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * When a clinic or service is open during the week: its name, the days it is
 * held, from when to when, and a note ("By appointment"). Shown as a weekly
 * table at /schedule, and today's on the home page.
 */
class ClinicSession extends Model
{
    /** @use HasFactory<ClinicSessionFactory> */
    use HasFactory, InTwoLanguages;

    /** The days of the week as Carbon numbers them, with their names. In the order a week is read in Thailand: Monday first. */
    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];

    /** "08:00", "16:30": hours and minutes on the 24-hour clock. */
    public const TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    protected $fillable = ['name', 'name_en', 'department_id', 'days', 'starts_at', 'ends_at', 'note', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Shown on the site, earliest first. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('starts_at')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * What is held on a day of the week (0 = Sunday ... 6 = Saturday), earliest first.
     *
     * @return Collection<int, ClinicSession>
     */
    public static function heldOn(int $day): Collection
    {
        // Filtered here, not in the query: the days are a short list in one column, and the table is a few dozen rows.
        return static::shown()->with('department')->get()->filter(fn (ClinicSession $session) => $session->isHeldOn($day))->values();
    }

    /** What is held today, by the clock of Thailand. */
    public static function today(): Collection
    {
        return static::heldOn(now()->dayOfWeek);
    }

    public function isHeldOn(int $day): bool
    {
        return in_array($day, array_map('intval', $this->days ?? []), true);
    }

    public function localName(): string
    {
        return $this->inLanguage('name');
    }

    /** "08.00–16.00", or "24 hours" for one that runs from midnight to midnight. */
    public function hours(): string
    {
        if ($this->starts_at === '00:00' && in_array($this->ends_at, ['23:59', '24:00', '00:00'], true)) {
            return __('24 hours');
        }

        return str_replace(':', '.', $this->starts_at).'–'.str_replace(':', '.', $this->ends_at);
    }

    /** Whether it is going on at this moment (for the mark beside today's sessions). */
    public function isOpenNow(): bool
    {
        $now = now()->format('H:i');

        return $this->isHeldOn(now()->dayOfWeek) && $now >= $this->starts_at && ($now < $this->ends_at || $this->ends_at === '00:00');
    }
}
