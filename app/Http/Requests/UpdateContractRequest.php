<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'contract_type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'status' => ['nullable', 'string', 'in:draft,validated,active,cancelled,expired'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'reduction_amount' => ['nullable', 'integer', 'min:0'],
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
