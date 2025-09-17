<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessSector;

class BusinessSectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = BusinessSector::getDefaultSectors();
        
        foreach ($sectors as $sectorData) {
            BusinessSector::create($sectorData);
        }
    }
}
