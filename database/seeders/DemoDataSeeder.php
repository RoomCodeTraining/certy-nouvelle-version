<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\CirculationZone;
use App\Models\Color;
use App\Models\EnergySource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée des données de démonstration : 100 clients, 2 véhicules par client,
 * contrats actifs dont ~30 % arrivent à échéance dans les 7 prochains jours
 * (pour tester le renouvellement et les alertes).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des données démo…');

        // Organisation et utilisateur
        $organization = Organization::first();
        if (! $organization) {
            $organization = Organization::create([
                'name' => 'Organisation Démo',
                'slug' => 'demo-' . substr(md5(uniqid()), 0, 8),
            ]);
            $this->command->info("Organisation créée : {$organization->name}");
        }

        $user = User::first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Utilisateur Démo',
                'email' => 'demo@example.com',
            ]);
            $this->command->info("Utilisateur créé : {$user->email}");
        }

        if (! DB::table('organization_user')->where('organization_id', $organization->id)->where('user_id', $user->id)->exists()) {
            DB::table('organization_user')->insert([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $company = Company::first();
        if (! $company) {
            $this->command->error('Aucune compagnie en base. Exécutez d\'abord : php artisan db:seed --class=CompanySeeder');
            return;
        }

        $brands = VehicleBrand::with('models')->get();
        if ($brands->isEmpty()) {
            $this->command->error('Aucune marque/modèle. Exécutez d\'abord : php artisan db:seed --class=VehicleBrandModelSeeder');
            return;
        }

        $zone = CirculationZone::first();
        $energy = EnergySource::where('code', 'SEES')->first() ?? EnergySource::first();
        $colors = Color::all();

        $prenoms = [
            'Aïcha', 'Amadou', 'Bakary', 'Fatou', 'Ibrahim', 'Kadiatou', 'Mamadou', 'Marie', 'Ousmane', 'Seydou',
            'Abdoul', 'Adama', 'Bintou', 'Boubacar', 'Fanta', 'Ibrahima', 'Mariama', 'Modibo', 'Saliou', 'Yacouba',
        ];
        $noms = [
            'Koné', 'Traoré', 'Ouattara', 'Diallo', 'Touré', 'Coulibaly', 'Sangaré', 'Bamba', 'Keita', 'Diop',
            'Bakayoko', 'Camara', 'Ouédraogo', 'Sawadogo', 'Soré', 'Ouédraogo', 'Kaboré', 'Zoungrana', 'Sankara', 'Some',
        ];

        $today = now()->startOfDay();

        $contractsExpiringSoon = 0;
        $contractsActive = 0;

        for ($i = 1; $i <= 100; $i++) {
            $client = Client::create([
                'organization_id' => $organization->id,
                'owner_id' => $user->id,
                'full_name' => $prenoms[array_rand($prenoms)] . ' ' . $noms[array_rand($noms)] . ' #' . $i,
                'email' => 'client.demo' . $i . '@example.com',
                'phone' => '+225 07 00 00 ' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'address' => $i . ' Avenue des Fakes, Abidjan',
                'type_assure' => $i % 3 === 0 ? 'TAPM' : 'TAPP',
                'reference' => Client::generateUniqueReference(),
            ]);

            // 2 véhicules par client
            for ($v = 1; $v <= 2; $v++) {
                $brand = $brands->random();
                $model = $brand->models->random();

                $yearFirstReg = now()->year - rand(1, 8);
                $vehicle = Vehicle::create([
                    'client_id' => $client->id,
                    'vehicle_brand_id' => $brand->id,
                    'vehicle_model_id' => $model->id,
                    'pricing_type' => ['VP', 'VP', 'VP', 'TPC', 'TPM'][array_rand(['VP', 'VP', 'VP', 'TPC', 'TPM'])],
                    'registration_number' => 'CI-' . strtoupper(substr(md5($client->id . $v), 0, 4)) . '-' . str_pad(rand(1000, 9999), 4, '0'),
                    'circulation_zone_id' => $zone?->id,
                    'energy_source_id' => $energy?->id,
                    'color_id' => $colors->isNotEmpty() ? $colors->random()->id : null,
                    'seat_count' => rand(4, 9),
                    'year_of_first_registration' => $yearFirstReg,
                    'first_registration_date' => \Carbon\Carbon::create($yearFirstReg, rand(1, 12), rand(1, 28))->format('Y-m-d'),
                    'registration_card_number' => 'CG-' . strtoupper(substr(md5($client->id . $v . uniqid()), 0, 8)) . '-' . str_pad(rand(100000, 999999), 6, '0'),
                    'chassis_number' => strtoupper(substr(md5($client->id . $v . $i . uniqid()), 0, 17)),
                    'reference' => Vehicle::generateUniqueReference(),
                ]);

                // 1 contrat par véhicule
                $isExpiringSoon = ($contractsExpiringSoon < 60) && (rand(1, 100) <= 35);
                if ($isExpiringSoon) {
                    $contractsExpiringSoon++;
                    $daysUntilExpiry = rand(1, 7);
                    $end = $today->copy()->addDays($daysUntilExpiry);
                    $start = $end->copy()->subYear();
                } else {
                    $contractsActive++;
                    $daysUntilExpiry = rand(30, 180);
                    $end = $today->copy()->addDays($daysUntilExpiry);
                    $start = $end->copy()->subYear();
                }

                $rcAmount = rand(45000, 120000);
                $taxes = (int) round($rcAmount * 0.145);
                $totalAmount = $rcAmount + $taxes + rand(5000, 15000);

                Contract::create([
                    'organization_id' => $organization->id,
                    'client_id' => $client->id,
                    'vehicle_id' => $vehicle->id,
                    'company_id' => $company->id,
                    'parent_id' => null,
                    'contract_type' => $vehicle->pricing_type,
                    'status' => Contract::STATUS_ACTIVE,
                    'start_date' => $start,
                    'end_date' => $end,
                    'reference' => Contract::generateUniqueReference(),
                    'policy_number' => 'POL-' . str_pad(rand(100000, 999999), 6, '0'),
                    'attestation_number' => 'ATT-' . str_pad(rand(100000, 999999), 6, '0'),
                    'rc_amount' => $rcAmount,
                    'defence_appeal_amount' => 0,
                    'person_transport_amount' => 0,
                    'optional_guarantees_amount' => 0,
                    'accessory_amount' => rand(5000, 15000),
                    'taxes_amount' => $taxes,
                    'cedeao_amount' => 0,
                    'fga_amount' => 0,
                    'total_amount' => $totalAmount,
                    'created_by_id' => $user->id,
                    'updated_by_id' => $user->id,
                ]);
            }

            if ($i % 20 === 0) {
                $this->command->info("  {$i}/100 clients créés…");
            }
        }

        $this->command->info('Données démo créées :');
        $this->command->info('  - 100 clients');
        $this->command->info('  - 200 véhicules (2 par client, 1 contrat par véhicule)');
        $this->command->info('  - 200 contrats');
        $this->command->info("  - ~{$contractsExpiringSoon} contrats à échéance dans 7 jours (pour tester renouvellement)");
    }
}
