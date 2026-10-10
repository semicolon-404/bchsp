<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /** Showing now, with no end, unless a state says otherwise. */
    public function definition(): array
    {
        return [
            'title' => 'Announcement '.fake()->unique()->numberBetween(1, 100000),
            'starts_at' => now()->subDay(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['starts_at' => now()->addDay()]);
    }

    public function ended(): static
    {
        return $this->state(fn () => ['starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
