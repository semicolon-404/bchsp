<?php

namespace Database\Factories;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Complaint> */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'code' => 'CMP-690101-'.strtoupper(fake()->unique()->bothify('??##')),
            // The code of every one made here is 123456.
            'pin_hash' => Hash::make('123456'),
            'kind' => 'service',
            'title' => fake()->sentence(4),
            'detail' => fake()->paragraph(),
            'is_anonymous' => true,
            'status' => 'received',
        ];
    }
}
