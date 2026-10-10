<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogCategory>
 */
class BlogCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Category '.fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
