<?php

namespace Database\Seeders;

use App\Enums\TwoWheelerPowerRangeEnum;
use App\Models\TwoWheelerPricingGrid;
use Illuminate\Database\Seeder;

class TwoWheelerPricingGridSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricing = [
            [
                'power_range' => TwoWheelerPowerRangeEnum::RANGE_INFERIOR_TO_50->value,
                'base_amount' => 15349,
                'rc_amount' => 11642,
                'defence_appeal_amount' => 1060,
                'person_transport_amount' => 0,
                'accessory_amount' => 500,
                'taxes_amount' => 1914,
                'cedeao_amount' => 0,
                'fga_amount' => 233,
            ],
            [
                'power_range' => TwoWheelerPowerRangeEnum::RANGE_51_99->value,
                'base_amount' => 20769,
                'rc_amount' => 15294,
                'defence_appeal_amount' => 1060,
                'person_transport_amount' => 0,
                'accessory_amount' => 500,
                'taxes_amount' => 2589,
                'cedeao_amount' => 0,
                'fga_amount' => 326,
            ],
            [
                'power_range' => TwoWheelerPowerRangeEnum::RANGE_100_175->value,
                'base_amount' => 29567,
                'rc_amount' => 23846,
                'defence_appeal_amount' => 1060,
                'person_transport_amount' => 0,
                'accessory_amount' => 500,
                'taxes_amount' => 3684,
                'cedeao_amount' => 0,
                'fga_amount' => 477,
            ],
            [
                'power_range' => TwoWheelerPowerRangeEnum::RANGE_176_350->value,
                'base_amount' => 37503,
                'rc_amount' => 30658,
                'defence_appeal_amount' => 1060,
                'person_transport_amount' => 0,
                'accessory_amount' => 500,
                'taxes_amount' => 4673,
                'cedeao_amount' => 0,
                'fga_amount' => 613,
            ],
            [
                'power_range' => TwoWheelerPowerRangeEnum::RANGE_350_PLUS->value,
                'base_amount' => 49412,
                'rc_amount' => 40880,
                'defence_appeal_amount' => 1060,
                'person_transport_amount' => 0,
                'accessory_amount' => 500,
                'taxes_amount' => 6154,
                'cedeao_amount' => 0,
                'fga_amount' => 818,
            ],
        ];

        foreach ($pricing as $row) {
            TwoWheelerPricingGrid::updateOrCreate(
                ['power_range' => $row['power_range']],
                [
                    'base_amount' => $row['base_amount'],
                    'rc_amount' => $row['rc_amount'],
                    'defence_appeal_amount' => $row['defence_appeal_amount'],
                    'person_transport_amount' => $row['person_transport_amount'],
                    'accessory_amount' => $row['accessory_amount'],
                    'taxes_amount' => $row['taxes_amount'],
                    'cedeao_amount' => $row['cedeao_amount'],
                    'fga_amount' => $row['fga_amount'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Grille tarifaire deux roues créée avec succès !');
    }
}
