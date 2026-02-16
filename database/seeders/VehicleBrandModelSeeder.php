<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleBrandModelSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Toyota' => ['Corolla', 'Camry', 'RAV4', 'Yaris', 'Hilux', 'Land Cruiser', 'Prado'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Jazz', 'HR-V'],
            'Nissan' => ['Sunny', 'Sentra', 'Qashqai', 'X-Trail', 'Navara'],
            'Hyundai' => ['i10', 'i20', 'Elantra', 'Tucson', 'Santa Fe', 'Accent', 'Creta'],
            'Kia' => ['Picanto', 'Rio', 'Cerato', 'Sportage', 'Sorento', 'Ceed', 'Seltos'],
            'Renault' => ['Clio', 'Megane', 'Duster', 'Captur', 'Logan', 'Symbol', 'Fluence'],
            'Peugeot' => ['208', '207', '301', '308', '3008', '5008', '406', '407', 'Partner', 'Boxer'],
            'Citroen' => ['C3', 'C4', 'C-Elysee', 'C5 Aircross'],
            'Volkswagen' => ['Polo', 'Golf', 'Jetta', 'Tiguan', 'Passat', 'T-Cross'],
            'Ford' => ['Fiesta', 'Focus', 'EcoSport', 'Ranger', 'Kuga'],
            'Suzuki' => ['Swift', 'Baleno', 'Vitara', 'Jimny', 'Alto', 'Dzire'],
            'Mitsubishi' => ['Lancer', 'Outlander', 'ASX', 'Pajero Sport', 'L200'],
            'Mazda' => ['Mazda2', 'Mazda3', 'CX-3', 'CX-5'],
            'BMW' => ['1 Series', '3 Series', '5 Series', 'X3', 'X5'],
            'Mercedes-Benz' => ['A-Class', 'C-Class', 'E-Class', 'GLC', 'GLE'],
            'Audi' => ['A3', 'A4', 'Q3', 'Q5', 'A6'],
            'Chevrolet' => ['Spark', 'Aveo', 'Cruze', 'Tracker'],
            'Great Wall' => ['Wingle 5', 'Wingle 7', 'H6'],
            'Chery' => ['Tiggo 2', 'Tiggo 7', 'Arrizo 5'],
            'Dacia' => ['Sandero', 'Duster', 'Logan'],
            'Isuzu' => ['D-Max'],
            'Jeep' => ['Wrangler', 'Grand Cherokee'],
            'Land Rover' => ['Defender', 'Discovery', 'Range Rover Sport'],
            'Skoda' => ['Fabia', 'Octavia', 'Kodiaq'],
            'Seat' => ['Ibiza', 'Leon', 'Arona'],
            'Opel' => ['Corsa', 'Astra', 'Mokka'],
            'Fiat' => ['Panda', 'Tipo', '500X'],
            'BYD' => ['Atto 3', 'Dolphin'],
            'Geely' => ['Coolray', 'Emgrand'],
            'Changan' => ['CS35', 'CS55'],
            'Haval' => ['H2', 'H6'],
            'Bestune' => ['T33', 'T55', 'T77', 'T99', 'B70', 'B30'],
        ];

        foreach ($data as $brandName => $models) {
            $brand = VehicleBrand::firstOrCreate(
                ['name' => $brandName],
                ['name' => $brandName]
            );

            foreach ($models as $modelName) {
                VehicleModel::firstOrCreate(
                    [
                        'vehicle_brand_id' => $brand->id,
                        'name' => $modelName,
                    ],
                    [
                        'vehicle_brand_id' => $brand->id,
                        'name' => $modelName,
                    ]
                );
            }
        }
    }
}
