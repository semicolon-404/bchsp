<?php

namespace Database\Factories;

use App\Models\ItaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItaItem>
 */
class ItaItemFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 100000);

        return [
            'year' => 2569,
            'number' => $number,
            'code' => 'MOIT '.$number,
            'title' => 'Item '.$number,
        ];
    }
}
