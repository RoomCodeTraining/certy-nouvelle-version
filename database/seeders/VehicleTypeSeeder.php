<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'TV01', 'description' => 'Ambulance'],
            ['code' => 'TV02', 'description' => 'Auto Car (Plus de 41 Places passager – sans chauffeur)'],
            ['code' => 'TV03', 'description' => 'Corbillard'],
            ['code' => 'TV04', 'description' => 'Mini Car (9 à 40 Places passager – sans chauffeur)'],
            ['code' => 'TV05', 'description' => 'Taxi Communaux'],
            ['code' => 'TV06', 'description' => 'Taxi Urbain (MATCA, VTC, …)'],
            ['code' => 'TV07', 'description' => 'Véhicule Auto-École'],
            ['code' => 'TV08', 'description' => 'Véhicule De Service Public (Véhicule de l\'État, Véhicule de ramassage d\'ordures)'],
            ['code' => 'TV09', 'description' => 'Véhicule De Tourisme (Maximum 9 Places – avec chauffeur)'],
            ['code' => 'TV10', 'description' => 'Véhicule Particulier (Voiture, Véhicule de tourisme avec un PTAC maximum de 3,5 Tonnes)'],
            ['code' => 'TV11', 'description' => 'Véhicule Utilitaire (Van, Fourgonnette, Camionnette, Camion, Tracteur routier, Tracteur agricole)'],
            ['code' => 'TV12', 'description' => 'Voiture de Location'],
            ['code' => 'TV13', 'description' => 'Cyclomoteur (2/3 Roues)'],
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['description'], 'code' => $type['code']]
            );
        }
    }
}
