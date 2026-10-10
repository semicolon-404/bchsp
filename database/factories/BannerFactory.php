<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /** A wide banner, showing now with no end, unless a state says otherwise. */
    public function definition(): array
    {
        return [
            'title' => 'Banner '.fake()->unique()->numberBetween(1, 100000),
            'image_path' => 'banners/'.fake()->unique()->numberBetween(1, 100000).'.webp',
            'starts_at' => now()->subDay(),
        ];
    }

    public function poster(): static
    {
        return $this->state(fn () => ['placement' => 'poster']);
    }

    public function popup(): static
    {
        return $this->state(fn () => ['is_popup' => true]);
    }
}
