<?php

namespace Database\Seeders;

use App\Models\CirculationZone;
use App\Models\Client;
use App\Models\Color;
use App\Models\EnergySource;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleGender;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use Illuminate\Database\Seeder;

/**
 * Jeu de données léger pour tester la création de contrats (dont CP).
 * Clients + véhicules VP / TPC / TPM / deux-roues avec champs tarifaires renseignés.
 */
class TestClientVehicleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        if (! $organization) {
            $this->command->error('Aucune organisation. Créez-en une via l’onboarding ou DatabaseSeeder.');

            return;
        }

        $owner = User::query()->where('is_root', true)->first()
            ?? User::query()->orderBy('id')->first();
        if (! $owner) {
            $this->command->error('Aucun utilisateur en base.');

            return;
        }

        $brand = VehicleBrand::with('models')->whereHas('models')->first();
        if (! $brand || $brand->models->isEmpty()) {
            $this->command->error('Aucune marque/modèle. Exécutez VehicleBrandModelSeeder.');

            return;
        }

        $essence = EnergySource::where('code', 'SEES')->first() ?? EnergySource::first();
        $diesel = EnergySource::where('code', 'SEDI')->first() ?? $essence;
        $zone = CirculationZone::first();
        $color = Color::first();
        $usage = VehicleUsage::first();
        $type = VehicleType::first();
        $category = VehicleCategory::first();
        $gender = VehicleGender::first();

        $model = $brand->models->first();
        $modelAlt = $brand->models->skip(1)->first() ?? $model;

        $fixtures = [
            [
                'client' => [
                    'full_name' => 'Amadou Koné (Test VP)',
                    'email' => 'test.vp@example.com',
                    'phone' => '+225 07 11 22 33 01',
                    'address' => 'Cocody, Abidjan',
                    'type_assure' => Client::TYPE_TAPP,
                ],
                'vehicles' => [
                    [
                        'pricing_type' => 'VP',
                        'registration_number' => 'CI-TEST-VP-001',
                        'fiscal_power' => 7,
                        'energy_source_id' => $essence?->id,
                        'seat_count' => 5,
                        'body_type' => 'Berline',
                        'new_value' => 12_000_000,
                        'replacement_value' => 8_500_000,
                    ],
                    [
                        'pricing_type' => 'VP',
                        'registration_number' => 'CI-TEST-VP-002',
                        'fiscal_power' => 5,
                        'energy_source_id' => $diesel?->id,
                        'seat_count' => 5,
                        'body_type' => 'SUV',
                        'new_value' => 15_000_000,
                        'replacement_value' => 10_000_000,
                    ],
                ],
            ],
            [
                'client' => [
                    'full_name' => 'Fatou Diallo (Test TPC)',
                    'email' => 'test.tpc@example.com',
                    'phone' => '+225 07 11 22 33 02',
                    'address' => 'Yopougon, Abidjan',
                    'type_assure' => Client::TYPE_TAPM,
                ],
                'vehicles' => [
                    [
                        'pricing_type' => 'TPC',
                        'registration_number' => 'CI-TEST-TPC-001',
                        'payload_capacity' => 3.5,
                        'fiscal_power' => 9,
                        'energy_source_id' => $diesel?->id,
                        'seat_count' => 3,
                        'body_type' => 'Camionnette',
                        'new_value' => 18_000_000,
                        'replacement_value' => 12_000_000,
                    ],
                ],
            ],
            [
                'client' => [
                    'full_name' => 'Ibrahim Traoré (Test TPM)',
                    'email' => 'test.tpm@example.com',
                    'phone' => '+225 07 11 22 33 03',
                    'address' => 'Plateau, Abidjan',
                    'type_assure' => Client::TYPE_TAPM,
                ],
                'vehicles' => [
                    [
                        'pricing_type' => 'TPM',
                        'registration_number' => 'CI-TEST-TPM-001',
                        'payload_capacity' => 5.0,
                        'fiscal_power' => 11,
                        'energy_source_id' => $diesel?->id,
                        'seat_count' => 3,
                        'body_type' => 'Camion',
                        'new_value' => 25_000_000,
                        'replacement_value' => 16_000_000,
                    ],
                    [
                        'pricing_type' => 'TPM',
                        'registration_number' => 'CI-TEST-TPM-002',
                        'payload_capacity' => 3.0,
                        'fiscal_power' => 8,
                        'energy_source_id' => $diesel?->id,
                        'seat_count' => 5,
                        'body_type' => 'Pick-up double cabine',
                        'new_value' => 14_000_000,
                        'replacement_value' => 9_000_000,
                    ],
                ],
            ],
            [
                'client' => [
                    'full_name' => 'Marie Ouattara (Test 2-roues)',
                    'email' => 'test.2roues@example.com',
                    'phone' => '+225 07 11 22 33 04',
                    'address' => 'Marcory, Abidjan',
                    'type_assure' => Client::TYPE_TAPP,
                ],
                'vehicles' => [
                    [
                        'pricing_type' => 'TWO_WHEELER',
                        'registration_number' => 'CI-TEST-2R-001',
                        'engine_capacity' => 125,
                        'fiscal_power' => 2,
                        'energy_source_id' => $essence?->id,
                        'seat_count' => 2,
                        'body_type' => 'Moto',
                        'new_value' => 1_200_000,
                        'replacement_value' => 800_000,
                    ],
                ],
            ],
        ];

        $clientCount = 0;
        $vehicleCount = 0;

        foreach ($fixtures as $index => $fixture) {
            $clientData = $fixture['client'];
            $client = Client::query()
                ->where('organization_id', $organization->id)
                ->where('email', $clientData['email'])
                ->first();

            if (! $client) {
                $client = Client::create([
                    ...$clientData,
                    'organization_id' => $organization->id,
                    'owner_id' => $owner->id,
                    'reference' => Client::generateUniqueReference(),
                ]);
                $clientCount++;
            }

            foreach ($fixture['vehicles'] as $vIndex => $vehicleData) {
                $existing = Vehicle::query()
                    ->where('client_id', $client->id)
                    ->where('registration_number', $vehicleData['registration_number'])
                    ->first();

                if ($existing) {
                    continue;
                }

                Vehicle::create([
                    ...$vehicleData,
                    'client_id' => $client->id,
                    'vehicle_brand_id' => $brand->id,
                    'vehicle_model_id' => $vIndex === 0 ? $model->id : $modelAlt->id,
                    'circulation_zone_id' => $zone?->id,
                    'vehicle_usage_id' => $usage?->id,
                    'vehicle_type_id' => $type?->id,
                    'vehicle_category_id' => $category?->id,
                    'vehicle_gender_id' => $gender?->id,
                    'color_id' => $color?->id,
                    'year_of_first_registration' => 2021,
                    'first_registration_date' => '2021-06-15',
                    'registration_card_number' => 'CG-TEST-'.str_pad((string) ($index + 1).($vIndex + 1), 6, '0', STR_PAD_LEFT),
                    'chassis_number' => strtoupper(substr(md5($clientData['email'].$vehicleData['registration_number']), 0, 17)),
                    'reference' => Vehicle::generateUniqueReference(),
                ]);
                $vehicleCount++;
            }
        }

        $this->command->info("TestClientVehicleSeeder OK — org « {$organization->name} », owner {$owner->email}");
        $this->command->info("  +{$clientCount} client(s), +{$vehicleCount} véhicule(s)");
        $this->command->info('  Clients : Amadou Koné (VP), Fatou Diallo (TPC), Ibrahim Traoré (TPM), Marie Ouattara (2-roues)');
    }
}
