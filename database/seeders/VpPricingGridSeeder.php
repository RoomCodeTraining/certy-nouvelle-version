<?php

namespace Database\Seeders;

use App\Enums\ContractDurationEnum;
use App\Enums\VpPowerRangeEnum;
use App\Models\EnergySource;
use App\Models\VpPricingGrid;
use Illuminate\Database\Seeder;

class VpPricingGridSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requires EnergySourceSeeder to have run (codes SEES and SEDI).
     */
    public function run(): void
    {
        $diesel = EnergySource::where('code', 'SEDI')->first();
        $essence = EnergySource::where('code', 'SEES')->first();

        if (! $diesel || ! $essence) {
            $this->command->warn('VpPricingGridSeeder: EnergySource with code SEDI or SEES not found. Run EnergySourceSeeder first.');

            return;
        }

        $essencePricing = $this->getEssencePricing();
        $dieselPricing = $this->getDieselPricing();

        foreach ($essencePricing as $duration => $powerRanges) {
            foreach ($powerRanges as $row) {
                VpPricingGrid::updateOrCreate(
                    [
                        'energy_source_id' => $essence->id,
                        'duration' => $duration,
                        'power_range' => $row['power_range'],
                    ],
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
        }

        foreach ($dieselPricing as $duration => $powerRanges) {
            foreach ($powerRanges as $row) {
                VpPricingGrid::updateOrCreate(
                    [
                        'energy_source_id' => $diesel->id,
                        'duration' => $duration,
                        'power_range' => $row['power_range'],
                    ],
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
        }

        $this->command->info('Grille tarifaire VP créée avec succès !');
    }

    private function getEssencePricing(): array
    {
        return [
            ContractDurationEnum::ONE_MONTH->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_3_6->value, 'rc_amount' => 6689, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 1339, 'cedeao_amount' => 1000, 'fga_amount' => 134, 'base_amount' => 11707],
                ['power_range' => VpPowerRangeEnum::RANGE_7_9->value, 'rc_amount' => 7342, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 1434, 'cedeao_amount' => 1000, 'fga_amount' => 147, 'base_amount' => 12468],
                ['power_range' => VpPowerRangeEnum::RANGE_10_11->value, 'rc_amount' => 11469, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 2032, 'cedeao_amount' => 1000, 'fga_amount' => 229, 'base_amount' => 17275],
                ['power_range' => VpPowerRangeEnum::RANGE_12_PLUS->value, 'rc_amount' => 12906, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 2240, 'cedeao_amount' => 1000, 'fga_amount' => 258, 'base_amount' => 18949],
            ],
            ContractDurationEnum::TWO_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_3_6->value, 'rc_amount' => 12708, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 2558, 'cedeao_amount' => 1000, 'fga_amount' => 254, 'base_amount' => 21456],
                ['power_range' => VpPowerRangeEnum::RANGE_7_9->value, 'rc_amount' => 13949, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 2738, 'cedeao_amount' => 1000, 'fga_amount' => 279, 'base_amount' => 22902],
                ['power_range' => VpPowerRangeEnum::RANGE_10_11->value, 'rc_amount' => 21792, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 3875, 'cedeao_amount' => 1000, 'fga_amount' => 436, 'base_amount' => 32039],
                ['power_range' => VpPowerRangeEnum::RANGE_12_PLUS->value, 'rc_amount' => 24521, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 4271, 'cedeao_amount' => 1000, 'fga_amount' => 490, 'base_amount' => 35218],
            ],
            ContractDurationEnum::THREE_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_3_6->value, 'rc_amount' => 18728, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 3778, 'cedeao_amount' => 1000, 'fga_amount' => 375, 'base_amount' => 31207],
                ['power_range' => VpPowerRangeEnum::RANGE_7_9->value, 'rc_amount' => 20556, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 4043, 'cedeao_amount' => 1000, 'fga_amount' => 411, 'base_amount' => 33336],
                ['power_range' => VpPowerRangeEnum::RANGE_10_11->value, 'rc_amount' => 32114, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 5719, 'cedeao_amount' => 1000, 'fga_amount' => 642, 'base_amount' => 46801],
                ['power_range' => VpPowerRangeEnum::RANGE_12_PLUS->value, 'rc_amount' => 36136, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 6302, 'cedeao_amount' => 1000, 'fga_amount' => 723, 'base_amount' => 51487],
            ],
            ContractDurationEnum::SIX_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_3_6->value, 'rc_amount' => 35449, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 7052, 'cedeao_amount' => 1000, 'fga_amount' => 709, 'base_amount' => 57399],
                ['power_range' => VpPowerRangeEnum::RANGE_7_9->value, 'rc_amount' => 38910, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 7554, 'cedeao_amount' => 1000, 'fga_amount' => 778, 'base_amount' => 61431],
                ['power_range' => VpPowerRangeEnum::RANGE_10_11->value, 'rc_amount' => 60787, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 10726, 'cedeao_amount' => 1000, 'fga_amount' => 1216, 'base_amount' => 86918],
                ['power_range' => VpPowerRangeEnum::RANGE_12_PLUS->value, 'rc_amount' => 68401, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 11830, 'cedeao_amount' => 1000, 'fga_amount' => 1368, 'base_amount' => 95788],
            ],
            ContractDurationEnum::TWELVE_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_3_6->value, 'rc_amount' => 66885, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 12954, 'cedeao_amount' => 1000, 'fga_amount' => 1338, 'base_amount' => 104627],
                ['power_range' => VpPowerRangeEnum::RANGE_7_9->value, 'rc_amount' => 73415, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 13900, 'cedeao_amount' => 1000, 'fga_amount' => 1468, 'base_amount' => 112233],
                ['power_range' => VpPowerRangeEnum::RANGE_10_11->value, 'rc_amount' => 114685, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 19886, 'cedeao_amount' => 1000, 'fga_amount' => 2294, 'base_amount' => 160323],
                ['power_range' => VpPowerRangeEnum::RANGE_12_PLUS->value, 'rc_amount' => 129058, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 21969, 'cedeao_amount' => 1000, 'fga_amount' => 2581, 'base_amount' => 177058],
            ],
        ];
    }

    private function getDieselPricing(): array
    {
        return [
            ContractDurationEnum::ONE_MONTH->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_2_4->value, 'rc_amount' => 6689, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 1339, 'cedeao_amount' => 1000, 'fga_amount' => 134, 'base_amount' => 11707],
                ['power_range' => VpPowerRangeEnum::RANGE_5_6->value, 'rc_amount' => 7342, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 1434, 'cedeao_amount' => 1000, 'fga_amount' => 147, 'base_amount' => 12468],
                ['power_range' => VpPowerRangeEnum::RANGE_7_8->value, 'rc_amount' => 11469, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 2032, 'cedeao_amount' => 1000, 'fga_amount' => 229, 'base_amount' => 17275],
                ['power_range' => VpPowerRangeEnum::RANGE_9_PLUS->value, 'rc_amount' => 12906, 'defence_appeal_amount' => 795, 'person_transport_amount' => 750, 'accessory_amount' => 1000, 'taxes_amount' => 2240, 'cedeao_amount' => 1000, 'fga_amount' => 258, 'base_amount' => 18949],
            ],
            ContractDurationEnum::TWO_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_2_4->value, 'rc_amount' => 12708, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 2558, 'cedeao_amount' => 1000, 'fga_amount' => 254, 'base_amount' => 21456],
                ['power_range' => VpPowerRangeEnum::RANGE_5_6->value, 'rc_amount' => 13949, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 2738, 'cedeao_amount' => 1000, 'fga_amount' => 279, 'base_amount' => 22902],
                ['power_range' => VpPowerRangeEnum::RANGE_7_8->value, 'rc_amount' => 21792, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 3875, 'cedeao_amount' => 1000, 'fga_amount' => 436, 'base_amount' => 32039],
                ['power_range' => VpPowerRangeEnum::RANGE_9_PLUS->value, 'rc_amount' => 24521, 'defence_appeal_amount' => 1511, 'person_transport_amount' => 1425, 'accessory_amount' => 2000, 'taxes_amount' => 4271, 'cedeao_amount' => 1000, 'fga_amount' => 490, 'base_amount' => 35218],
            ],
            ContractDurationEnum::THREE_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_2_4->value, 'rc_amount' => 18728, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 3778, 'cedeao_amount' => 1000, 'fga_amount' => 375, 'base_amount' => 31207],
                ['power_range' => VpPowerRangeEnum::RANGE_5_6->value, 'rc_amount' => 20556, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 4043, 'cedeao_amount' => 1000, 'fga_amount' => 411, 'base_amount' => 33336],
                ['power_range' => VpPowerRangeEnum::RANGE_7_8->value, 'rc_amount' => 32114, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 5719, 'cedeao_amount' => 1000, 'fga_amount' => 642, 'base_amount' => 46801],
                ['power_range' => VpPowerRangeEnum::RANGE_9_PLUS->value, 'rc_amount' => 36136, 'defence_appeal_amount' => 2226, 'person_transport_amount' => 2100, 'accessory_amount' => 3000, 'taxes_amount' => 6302, 'cedeao_amount' => 1000, 'fga_amount' => 723, 'base_amount' => 51487],
            ],
            ContractDurationEnum::SIX_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_2_4->value, 'rc_amount' => 35449, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 7052, 'cedeao_amount' => 1000, 'fga_amount' => 709, 'base_amount' => 57399],
                ['power_range' => VpPowerRangeEnum::RANGE_5_6->value, 'rc_amount' => 38910, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 7554, 'cedeao_amount' => 1000, 'fga_amount' => 778, 'base_amount' => 61431],
                ['power_range' => VpPowerRangeEnum::RANGE_7_8->value, 'rc_amount' => 60787, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 10726, 'cedeao_amount' => 1000, 'fga_amount' => 1216, 'base_amount' => 86918],
                ['power_range' => VpPowerRangeEnum::RANGE_9_PLUS->value, 'rc_amount' => 68401, 'defence_appeal_amount' => 4214, 'person_transport_amount' => 3975, 'accessory_amount' => 5000, 'taxes_amount' => 11830, 'cedeao_amount' => 1000, 'fga_amount' => 1368, 'base_amount' => 95788],
            ],
            ContractDurationEnum::TWELVE_MONTHS->value => [
                ['power_range' => VpPowerRangeEnum::RANGE_2_4->value, 'rc_amount' => 66885, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 12954, 'cedeao_amount' => 1000, 'fga_amount' => 1338, 'base_amount' => 104627],
                ['power_range' => VpPowerRangeEnum::RANGE_5_6->value, 'rc_amount' => 73415, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 13900, 'cedeao_amount' => 1000, 'fga_amount' => 1468, 'base_amount' => 112233],
                ['power_range' => VpPowerRangeEnum::RANGE_7_8->value, 'rc_amount' => 114685, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 19886, 'cedeao_amount' => 1000, 'fga_amount' => 2294, 'base_amount' => 160323],
                ['power_range' => VpPowerRangeEnum::RANGE_9_PLUS->value, 'rc_amount' => 129058, 'defence_appeal_amount' => 7950, 'person_transport_amount' => 7500, 'accessory_amount' => 7000, 'taxes_amount' => 21969, 'cedeao_amount' => 1000, 'fga_amount' => 2581, 'base_amount' => 177058],
            ],
        ];
    }
}
