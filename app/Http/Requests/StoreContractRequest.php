<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('parent_id') && $this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'contract_type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'parent_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'status' => ['nullable', 'string', 'in:draft,validated,active,cancelled,expired'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration' => ['nullable', 'string', 'in:1_month,3_months,6_months,12_months'],
            'reduction_amount' => ['nullable', 'integer', 'min:0'],
            'accessory_amount_override' => ['nullable', 'numeric', 'min:0'],
            'reduction_bns' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_amount' => ['nullable', 'integer', 'min:0'],
            'company_accessory' => ['nullable', 'integer', 'min:0'],
            'agency_accessory' => ['nullable', 'integer', 'min:0'],
            'commission_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
