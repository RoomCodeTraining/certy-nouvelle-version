<?php

namespace Database\Seeders;

use App\Models\EnergySource;
use Illuminate\Database\Seeder;

class EnergySourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            ['code' => 'SEES', 'name' => 'Essence'],
            ['code' => 'SEDI', 'name' => 'Diesel'],
            ['code' => 'GPL', 'name' => 'GPL'],
            ['code' => 'ELEC', 'name' => 'Électrique'],
        ];

        foreach ($sources as $source) {
            EnergySource::firstOrCreate(
                ['code' => $source['code']],
                ['name' => $source['name'], 'code' => $source['code']]
            );
        }
    }
}
