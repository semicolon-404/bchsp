<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /** Published and out, unless a state says otherwise. */
    public function definition(): array
    {
        return [
            'title' => 'Article '.fake()->unique()->numberBetween(1, 100000),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => BlogPost::STATUS_DRAFT, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->addDay()]);
    }
}
