<?php

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitePage>
 */
class SitePageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => 'page-'.fake()->unique()->numberBetween(1, 100000),
            'title' => 'Page '.fake()->unique()->numberBetween(1, 100000),
            'body' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
