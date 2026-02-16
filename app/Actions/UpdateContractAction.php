<?php

namespace App\Actions;

use App\Models\Contract;
use App\Services\ContractPricingService;

class UpdateContractAction
{
    public function __construct(
        protected ContractPricingService $pricingService
    ) {}

    public function execute(Contract $contract, array $validated): Contract
    {
        if (array_key_exists('reduction_amount', $validated)) {
            $validated['reduction_amount'] = (int) ($validated['reduction_amount'] ?? 0);
        }
        if (array_key_exists('reduction_bns', $validated)) {
            $validated['reduction_bns'] = (float) ($validated['reduction_bns'] ?? 0);
        }
        if (array_key_exists('reduction_on_commission', $validated)) {
            $validated['reduction_on_commission'] = (float) ($validated['reduction_on_commission'] ?? 0);
        }
        if (array_key_exists('reduction_on_profession_percent', $validated)) {
            $validated['reduction_on_profession_percent'] = $validated['reduction_on_profession_percent'] !== null && $validated['reduction_on_profession_percent'] !== '' ? (float) $validated['reduction_on_profession_percent'] : null;
        }
        if (array_key_exists('reduction_on_profession_amount', $validated)) {
            $validated['reduction_on_profession_amount'] = $validated['reduction_on_profession_amount'] !== null && $validated['reduction_on_profession_amount'] !== '' ? (int) $validated['reduction_on_profession_amount'] : null;
        }
        if (array_key_exists('company_accessory', $validated)) {
            $validated['company_accessory'] = (int) ($validated['company_accessory'] ?? 0);
        }
        if (array_key_exists('agency_accessory', $validated)) {
            $validated['agency_accessory'] = (int) ($validated['agency_accessory'] ?? 0);
        }
        if (array_key_exists('commission_amount', $validated)) {
            $validated['commission_amount'] = (int) ($validated['commission_amount'] ?? 0);
        }

        $contract->update($validated);
        $this->pricingService->applyToContract($contract);
        $contract->computeAndFillStoredAmounts();

        return $contract;
    }
}
