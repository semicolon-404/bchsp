<?php

namespace Database\Factories;

use App\Models\StaffLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffLink>
 */
class StaffLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Service '.fake()->unique()->numberBetween(1, 100000),
            'url' => 'https://example.com/'.fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
