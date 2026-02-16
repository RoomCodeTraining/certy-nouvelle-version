<?php

namespace App\Services;

use App\Enums\ContractDurationEnum;
use App\Enums\TpcPayloadRangeEnum;
use App\Enums\TpmPayloadRangeEnum;
use App\Enums\TwoWheelerPowerRangeEnum;
use App\Enums\VpPowerRangeEnum;
use App\Models\Contract;
use App\Models\TpcPricingGrid;
use App\Models\TpmPricingGrid;
use App\Models\TwoWheelerPricingGrid;
use App\Models\VpPricingGrid;
use Carbon\Carbon;

class ContractPricingService
{
    /**
     * Calcule les montants de prime selon le type de contrat et la grille tarifaire.
     * Retourne un tableau des montants (base_amount, rc_amount, etc.) ou vide si grille introuvable.
     */
    public function calculate(Contract $contract): array
    {
        $contract->loadMissing(['vehicle.energySource']);

        $vehicle = $contract->vehicle;
        if (! $vehicle) {
            return [];
        }

        return match ($contract->contract_type) {
            Contract::TYPE_VP => $this->calculateVp($contract, $vehicle),
            Contract::TYPE_TPC => $this->calculateTpc($contract, $vehicle),
            Contract::TYPE_TPM => $this->calculateTpm($contract, $vehicle),
            Contract::TYPE_TWO_WHEELER => $this->calculateTwoWheeler($vehicle),
            default => [],
        };
    }

    /**
     * Calcule les montants et les enregistre sur le contrat.
     */
    public function applyToContract(Contract $contract): bool
    {
        $amounts = $this->calculate($contract);
        if (empty($amounts)) {
            return false;
        }

        $contract->update($amounts);

        return true;
    }

    private function resolveDuration(Contract $contract): ?string
    {
        $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
        $end = $contract->end_date ? Carbon::parse($contract->end_date) : null;
        if (! $start || ! $end) {
            return null;
        }

        $months = $start->diffInMonths($end);
        if ($months <= 1) {
            return ContractDurationEnum::ONE_MONTH->value;
        }
        if ($months <= 2) {
            return ContractDurationEnum::TWO_MONTHS->value;
        }
        if ($months <= 3) {
            return ContractDurationEnum::THREE_MONTHS->value;
        }
        if ($months <= 6) {
            return ContractDurationEnum::SIX_MONTHS->value;
        }

        return ContractDurationEnum::TWELVE_MONTHS->value;
    }

    private function calculateVp(Contract $contract, $vehicle): array
    {
        $duration = $this->resolveDuration($contract);
        if (! $duration) {
            return [];
        }

        $energySourceId = $vehicle->energy_source_id;
        if (! $energySourceId) {
            return [];
        }

        $fiscalPower = (int) ($vehicle->fiscal_power ?? 0);
        $code = $vehicle->energySource?->code ?? '';

        $powerRange = $this->vpPowerRangeForFiscalPower($fiscalPower, $code);
        if (! $powerRange) {
            return [];
        }

        $row = VpPricingGrid::where('energy_source_id', $energySourceId)
            ->where('duration', $duration)
            ->where('power_range', $powerRange)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'base_amount' => $row->base_amount,
            'rc_amount' => $row->rc_amount,
            'defence_appeal_amount' => $row->defence_appeal_amount,
            'person_transport_amount' => $row->person_transport_amount,
            'accessory_amount' => $row->accessory_amount,
            'taxes_amount' => $row->taxes_amount,
            'cedeao_amount' => $row->cedeao_amount,
            'fga_amount' => $row->fga_amount,
        ];
    }

    /**
     * Mappe puissance fiscale (CV) + énergie (code) vers power_range VP.
     * Essence (SEES) : 3_6, 7_9, 10_11, 12_plus.
     * Diesel (SEDI) : 2_4, 5_6, 7_8, 9_plus.
     */
    private function vpPowerRangeForFiscalPower(int $fiscalPower, string $code): ?string
    {
        $isDiesel = strtoupper($code) === 'SEDI';

        if ($isDiesel) {
            if ($fiscalPower <= 4) {
                return VpPowerRangeEnum::RANGE_2_4->value;
            }
            if ($fiscalPower <= 6) {
                return VpPowerRangeEnum::RANGE_5_6->value;
            }
            if ($fiscalPower <= 8) {
                return VpPowerRangeEnum::RANGE_7_8->value;
            }

            return VpPowerRangeEnum::RANGE_9_PLUS->value;
        }

        if ($fiscalPower <= 6) {
            return VpPowerRangeEnum::RANGE_3_6->value;
        }
        if ($fiscalPower <= 9) {
            return VpPowerRangeEnum::RANGE_7_9->value;
        }
        if ($fiscalPower <= 11) {
            return VpPowerRangeEnum::RANGE_10_11->value;
        }

        return VpPowerRangeEnum::RANGE_12_PLUS->value;
    }

    private function calculateTpc(Contract $contract, $vehicle): array
    {
        $duration = $this->resolveDuration($contract);
        if (! $duration) {
            return [];
        }

        $payload = (float) ($vehicle->payload_capacity ?? 0);
        $payloadRange = $this->tpcPayloadRangeForTonnes($payload);

        $row = TpcPricingGrid::where('duration', $duration)
            ->where('payload_range', $payloadRange)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'base_amount' => $row->base_amount,
            'rc_amount' => $row->rc_amount,
            'defence_appeal_amount' => $row->defence_appeal_amount,
            'person_transport_amount' => $row->person_transport_amount,
            'accessory_amount' => $row->accessory_amount,
            'taxes_amount' => $row->taxes_amount,
            'cedeao_amount' => $row->cedeao_amount,
            'fga_amount' => $row->fga_amount,
        ];
    }

    private function tpcPayloadRangeForTonnes(float $tonnes): string
    {
        if ($tonnes <= 1) {
            return TpcPayloadRangeEnum::RANGE_INFERIOR_OR_EQUAL_TO_1->value;
        }
        if ($tonnes < 3) {
            return TpcPayloadRangeEnum::RANGE_1_3->value;
        }
        if ($tonnes < 5) {
            return TpcPayloadRangeEnum::RANGE_3_5->value;
        }
        if ($tonnes < 9) {
            return TpcPayloadRangeEnum::RANGE_5_8->value;
        }
        if ($tonnes < 12) {
            return TpcPayloadRangeEnum::RANGE_9_12->value;
        }
        if ($tonnes < 15) {
            return TpcPayloadRangeEnum::RANGE_12_15->value;
        }

        return TpcPayloadRangeEnum::RANGE_OVER_15->value;
    }

    private function calculateTpm(Contract $contract, $vehicle): array
    {
        $duration = $this->resolveDuration($contract);
        if (! $duration) {
            return [];
        }

        $payload = (float) ($vehicle->payload_capacity ?? 0);
        $payloadRange = $this->tpmPayloadRangeForTonnes($payload);

        $row = TpmPricingGrid::where('duration', $duration)
            ->where('payload_range', $payloadRange)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'base_amount' => $row->base_amount,
            'rc_amount' => $row->rc_amount,
            'defence_appeal_amount' => $row->defence_appeal_amount,
            'person_transport_amount' => $row->person_transport_amount,
            'accessory_amount' => $row->accessory_amount,
            'taxes_amount' => $row->taxes_amount,
            'cedeao_amount' => $row->cedeao_amount,
            'fga_amount' => $row->fga_amount,
        ];
    }

    private function tpmPayloadRangeForTonnes(float $tonnes): string
    {
        if ($tonnes <= 1) {
            return TpmPayloadRangeEnum::RANGE_INFERIOR_OR_EQUAL_TO_1->value;
        }
        if ($tonnes < 3) {
            return TpmPayloadRangeEnum::RANGE_1_3->value;
        }
        if ($tonnes < 5) {
            return TpmPayloadRangeEnum::RANGE_3_5->value;
        }
        if ($tonnes < 9) {
            return TpmPayloadRangeEnum::RANGE_5_8->value;
        }
        if ($tonnes < 12) {
            return TpmPayloadRangeEnum::RANGE_9_12->value;
        }
        if ($tonnes < 15) {
            return TpmPayloadRangeEnum::RANGE_12_15->value;
        }

        return TpmPayloadRangeEnum::RANGE_15_PLUS->value;
    }

    private function calculateTwoWheeler($vehicle): array
    {
        $engineCapacity = (int) ($vehicle->engine_capacity ?? 0);
        $powerRange = $this->twoWheelerPowerRangeForCc($engineCapacity);

        $row = TwoWheelerPricingGrid::where('power_range', $powerRange)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'base_amount' => $row->base_amount,
            'rc_amount' => $row->rc_amount,
            'defence_appeal_amount' => $row->defence_appeal_amount,
            'person_transport_amount' => $row->person_transport_amount,
            'accessory_amount' => $row->accessory_amount,
            'taxes_amount' => $row->taxes_amount,
            'cedeao_amount' => $row->cedeao_amount,
            'fga_amount' => $row->fga_amount,
        ];
    }

    private function twoWheelerPowerRangeForCc(int $cc): string
    {
        if ($cc < 50) {
            return TwoWheelerPowerRangeEnum::RANGE_INFERIOR_TO_50->value;
        }
        if ($cc < 100) {
            return TwoWheelerPowerRangeEnum::RANGE_51_99->value;
        }
        if ($cc < 176) {
            return TwoWheelerPowerRangeEnum::RANGE_100_175->value;
        }
        if ($cc <= 350) {
            return TwoWheelerPowerRangeEnum::RANGE_176_350->value;
        }

        return TwoWheelerPowerRangeEnum::RANGE_350_PLUS->value;
    }
}
