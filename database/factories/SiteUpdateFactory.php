<?php

namespace Database\Factories;

use App\Models\SiteUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteUpdate>
 */
class SiteUpdateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => '9.'.fake()->unique()->numberBetween(1, 100000),
            'title' => 'Release '.fake()->unique()->numberBetween(1, 100000),
            'items' => [['kind' => 'new', 'text' => 'Something new', 'text_en' => null]],
            'released_on' => now()->toDateString(),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn () => ['status' => SiteUpdate::STATUS_PLANNED, 'released_on' => null]);
    }
}
