<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessSectorSeeder::class,
            EmployeeCountSeeder::class,
            CompanySeeder::class,
            ServiceSeeder::class,
            EmployeeSeeder::class,
            ClientSeeder::class,
            FeedbackSeeder::class,
        ]);
    }
}