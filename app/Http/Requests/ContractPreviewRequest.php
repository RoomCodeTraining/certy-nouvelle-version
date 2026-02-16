<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'contract_type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
