<?php

namespace Database\Seeders;

use App\Models\VehicleUsage;
use Illuminate\Database\Seeder;

class VehicleUsageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usages = [
            ['code' => 'UV01', 'description' => 'Promenade ou Affaire'],
            ['code' => 'UV02', 'description' => 'Transport pour propre compte'],
            ['code' => 'UV03', 'description' => 'Transport privé de voyageurs'],
            ['code' => 'UV04', 'description' => 'Transport public de marchandises'],
            ['code' => 'UV05', 'description' => 'Transport public de voyageurs'],
            ['code' => 'UV06', 'description' => 'Véhicules Auto-école'],
            ['code' => 'UV07', 'description' => 'Véhicules de Location'],
            ['code' => 'UV08', 'description' => 'Véhicules Spéciaux'],
            ['code' => 'UV09', 'description' => 'Engin de Chantier'],
            ['code' => 'UV10', 'description' => 'Véhicule motorisé 2 à 3 roues'],
        ];

        foreach ($usages as $usage) {
            VehicleUsage::firstOrCreate(
                ['code' => $usage['code']],
                ['name' => $usage['description'], 'code' => $usage['code']]
            );
        }
    }
}
