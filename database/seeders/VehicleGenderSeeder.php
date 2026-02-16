<?php

namespace Database\Seeders;

use App\Models\VehicleGender;
use Illuminate\Database\Seeder;

class VehicleGenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genders = [
            ['code' => 'GV01', 'description' => 'Camion'],
            ['code' => 'GV02', 'description' => 'Camionnette'],
            ['code' => 'GV03', 'description' => 'Cyclomoteur (2/3 Roues)'],
            ['code' => 'GV04', 'description' => 'Voiture (4 Roues)'],
            ['code' => 'GV05', 'description' => 'Engins de chantiers'],
            ['code' => 'GV06', 'description' => 'Car'],
            ['code' => 'GV07', 'description' => 'Fourgonnette'],
            ['code' => 'GV08', 'description' => 'Remorque'],
            ['code' => 'GV09', 'description' => 'Scooter'],
            ['code' => 'GV10', 'description' => 'Semi-remorque'],
            ['code' => 'GV11', 'description' => 'Tracteur agricole'],
            ['code' => 'GV12', 'description' => 'Tracteur routier'],
        ];

        foreach ($genders as $gender) {
            VehicleGender::firstOrCreate(
                ['code' => $gender['code']],
                ['name' => $gender['description'], 'code' => $gender['code']]
            );
        }
    }
}
