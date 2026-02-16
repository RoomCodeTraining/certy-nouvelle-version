<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use App\Services\ContractPricingService;
use App\Services\PolicyNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class StoreContractAction
{
    public function __construct(
        protected ContractPricingService $pricingService,
        protected PolicyNumberService $policyNumberService
    ) {}

    public function execute(User $user, array $validated, $accessoryAmountOverride = null): Contract
    {
        if (empty($validated['end_date']) && ! empty($validated['start_date']) && ! empty($validated['duration'])) {
            $validated['end_date'] = $this->endDateFromDuration($validated['start_date'], $validated['duration']);
        }
        $organization = $user->currentOrganization();
        $validated['organization_id'] = $organization->id;
        $validated['created_by_id'] = $user->id;
        $validated['updated_by_id'] = $user->id;
        $validated['status'] = $validated['status'] ?? Contract::STATUS_DRAFT;
        $validated['reduction_amount'] = (int) ($validated['reduction_amount'] ?? 0);
        $validated['reduction_bns'] = (float) ($validated['reduction_bns'] ?? 0);
        $validated['reduction_on_commission'] = (float) ($validated['reduction_on_commission'] ?? 0);
        $validated['reduction_on_profession_percent'] = isset($validated['reduction_on_profession_percent']) && $validated['reduction_on_profession_percent'] !== '' ? (float) $validated['reduction_on_profession_percent'] : null;
        $validated['reduction_on_profession_amount'] = isset($validated['reduction_on_profession_amount']) && $validated['reduction_on_profession_amount'] !== '' ? (int) $validated['reduction_on_profession_amount'] : null;
        $validated['company_accessory'] = (int) ($validated['company_accessory'] ?? 0);
        $validated['agency_accessory'] = (int) ($validated['agency_accessory'] ?? 0);
        $validated['commission_amount'] = (int) ($validated['commission_amount'] ?? 0);
        unset($validated['duration'], $validated['accessory_amount_override']);
        if (Schema::hasColumn('contracts', 'reference')) {
            $validated['reference'] = Contract::generateUniqueReference();
        }
        if (Schema::hasColumn('contracts', 'parent_id')) {
            $validated['parent_id'] = isset($validated['parent_id']) && $validated['parent_id'] !== '' && $validated['parent_id'] !== null
                ? (int) $validated['parent_id']
                : null;
        } else {
            unset($validated['parent_id']);
        }

        // Règle métier : renouvellement avant échéance → même numéro de police ; après échéance → nouveau numéro
        if (Schema::hasColumn('contracts', 'policy_number')) {
            $reuseParentPolicyNumber = false;
            if (! empty($validated['parent_id'])) {
                $parent = Contract::find($validated['parent_id']);
                if ($parent
                    && $parent->policy_number
                    && ! empty($validated['start_date'])
                    && $parent->end_date
                    && Carbon::parse($validated['start_date'])->lte($parent->end_date)
                ) {
                    $reuseParentPolicyNumber = true;
                    $validated['policy_number'] = $parent->policy_number;
                }
            }
            if (! $reuseParentPolicyNumber) {
                $companyCode = isset($validated['company_id']) ? Company::find($validated['company_id'])?->code : null;
                do {
                    $validated['policy_number'] = $this->policyNumberService->generate($companyCode);
                } while (Contract::where('policy_number', $validated['policy_number'])->exists());
            }
        }

        $contract = Contract::create($validated);
        $this->pricingService->applyToContract($contract);

        if ($accessoryAmountOverride !== null && $accessoryAmountOverride !== '') {
            $contract->update(['accessory_amount' => (int) $accessoryAmountOverride]);
        }
        $contract->computeAndFillStoredAmounts();

        if ($contract->status === Contract::STATUS_VALIDATED) {
            Event::dispatch(new \App\Events\ContractValidated($contract, $user));
        }

        return $contract;
    }

    private function endDateFromDuration(string $startDate, string $duration): string
    {
        $start = Carbon::parse($startDate);
        $months = match ($duration) {
            '1_month' => 1,
            '3_months' => 3,
            '6_months' => 6,
            '12_months' => 12,
            default => 12,
        };
        return $start->copy()->addMonths($months)->subDay()->format('Y-m-d');
    }
}
