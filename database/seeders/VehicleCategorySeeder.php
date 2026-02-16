<?php

namespace Database\Seeders;

use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;

class VehicleCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => '01', 'description' => 'Véhicule de tourisme appartenant à des personnes physiques utilisés pour des besoins personnels et privés'],
            ['code' => '02', 'description' => 'Véhicule utilisé pour le transport de marchandises appartenant à l\'assuré ainsi que les véhicules de collectivité publique (arroseuses, balayeuses, voiture de vidange, goudronneuse, benne à ordure…)'],
            ['code' => '03', 'description' => 'Véhicule utilisé pour le transport de marchandises d\'un tiers'],
            ['code' => '04', 'description' => 'Véhicule muni d\'un taximètre, utilisé pour les transports mixtes'],
            ['code' => '05', 'description' => 'Véhicule motorisé 2 à 3 roues'],
            ['code' => '06', 'description' => 'Véhicule confié aux garagistes et professionnels de la réparation'],
            ['code' => '07', 'description' => 'Véhicule à usage d\'auto-écoles'],
            ['code' => '08', 'description' => 'Véhicule destiné à la location'],
            ['code' => '09', 'description' => 'Engin de chantier, utilisé par des entreprises industrielles dans l\'exécution de travaux de chantier'],
            ['code' => '10', 'description' => 'Véhicules spéciaux n\'entrant dans aucune catégorie de 1 à 9 et 11 à 12'],
            ['code' => '12', 'description' => 'Véhicule de tourisme appartenant à des personnes morales utilisés pour des besoins personnels et privés'],
        ];

        foreach ($categories as $category) {
            VehicleCategory::firstOrCreate(
                ['code' => $category['code']],
                ['name' => $category['description'], 'code' => $category['code']]
            );
        }
    }
}
