<?php

namespace Database\Factories;

use App\Models\WorkGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkGroup> */
class WorkGroupFactory extends Factory
{
    protected $model = WorkGroup::class;

    public function definition(): array
    {
        return [
            'name' => 'Group '.fake()->unique()->word(),
            'head_name' => fake()->name(),
            'head_position' => 'Nurse',
            'staff_count' => fake()->numberBetween(2, 40),
            'units' => ['Unit one', 'Unit two'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
