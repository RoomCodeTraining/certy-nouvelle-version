<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\CirculationZoneSeeder;
use Database\Seeders\ColorSeeder;
use Database\Seeders\EnergySourceSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TpcPricingGridSeeder;
use Database\Seeders\TpmPricingGridSeeder;
use Database\Seeders\TwoWheelerPricingGridSeeder;
use Database\Seeders\VpPricingGridSeeder;
use Database\Seeders\VehicleBrandModelSeeder;
use Database\Seeders\VehicleCategorySeeder;
use Database\Seeders\VehicleGenderSeeder;
use Database\Seeders\VehicleTypeSeeder;
use Database\Seeders\VehicleUsageSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);
        $this->call(VehicleBrandModelSeeder::class);
        $this->call(CirculationZoneSeeder::class);
        $this->call(VehicleCategorySeeder::class);
        $this->call(VehicleGenderSeeder::class);
        $this->call(VehicleTypeSeeder::class);
        $this->call(VehicleUsageSeeder::class);
        $this->call(ColorSeeder::class);
        $this->call(EnergySourceSeeder::class);
        $this->call(CompanySeeder::class);
        $this->call(TpcPricingGridSeeder::class);
        $this->call(TpmPricingGridSeeder::class);
        $this->call(TwoWheelerPricingGridSeeder::class);
        $this->call(VpPricingGridSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
