<?php

namespace Database\Factories;

use App\Models\AnnouncementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementType>
 */
class AnnouncementTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Type '.fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
