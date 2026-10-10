<?php

namespace Database\Factories;

use App\Models\ClinicSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicSession>
 */
class ClinicSessionFactory extends Factory
{
    /** Every weekday morning, unless a test says otherwise. */
    public function definition(): array
    {
        return [
            'name' => 'Clinic '.fake()->unique()->numberBetween(1, 100000),
            'days' => [1, 2, 3, 4, 5],
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ];
    }
}
