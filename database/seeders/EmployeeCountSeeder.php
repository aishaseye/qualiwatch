<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeCount;

class EmployeeCountSeeder extends Seeder
{
    public function run(): void
    {
        $counts = EmployeeCount::getDefaultCounts();
        
        foreach ($counts as $countData) {
            EmployeeCount::create($countData);
        }
    }
}