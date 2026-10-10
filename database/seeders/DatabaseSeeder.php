<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * A starting point for a new installation; each part adds nothing when there is already something there.
     * No accounts are made here: the first administrator comes from "php artisan admin:create".
     */
    public function run(): void
    {
        $this->call([DepartmentSeeder::class, StaffLinkSeeder::class]);
    }
}
