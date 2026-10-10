<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Document '.fake()->unique()->numberBetween(1, 100000),
            'file_path' => 'documents/'.fake()->unique()->numberBetween(1, 100000).'.pdf',
        ];
    }
}
