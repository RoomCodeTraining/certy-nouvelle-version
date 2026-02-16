<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            ['code' => 'ASACI_SUNU', 'name' => 'SUNU ASSURANCES', 'status' => false],
            ['code' => 'ASACI_SANLAM', 'name' => 'SANLAM ASSURANCES', 'status' => false],
            ['code' => 'ASACI_ALLIANZ', 'name' => 'ALLIANZ', 'status' => false],
            ['code' => 'ASACI_SCHIBA', 'name' => 'SCHIBA ASSURANCES', 'status' => false],
            ['code' => 'ASACI_SMABTP', 'name' => 'SMABTP', 'status' => false],
            ['code' => 'ASACI_AXA', 'name' => 'AXA CI', 'status' => false],
            ['code' => 'ASACI_ATLANTIQUE', 'name' => 'ATLANTIQUE', 'status' => false],
            ['code' => 'ASACI_ATLANTA', 'name' => 'ATLANTA', 'status' => false],
            ['code' => 'ASACI_AFG', 'name' => 'AFG ASSURANCES', 'status' => false],
            ['code' => 'ASACI_CORIS', 'name' => 'CORIS ASSURANCES', 'status' => false],
            ['code' => 'ASACI_SERENITY', 'name' => 'SERENITY SA', 'status' => false],
            ['code' => 'ASACI_LOYALE', 'name' => 'LOYALE', 'status' => false],
            ['code' => 'ASACI_SIDAM', 'name' => 'SIDAM', 'status' => false],
            ['code' => 'ASACI_AMSA', 'name' => 'AMSA', 'status' => false],
            ['code' => 'ASACI_GNACI', 'name' => 'GNA CI', 'status' => false],
            ['code' => 'ASACI_', 'name' => 'GNA CI', 'status' => false],
            ['code' => 'ASACI_LUNAR', 'name' => 'ASACI LUNAR', 'status' => true],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(
                ['code' => $company['code']],
                [
                    'name' => $company['name'],
                    'code' => $company['code'],
                    'is_active' => $company['status'],
                ]
            );
        }
    }
}
