<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Si le front envoie le type d’avenant sans creation_mode, on complète pour la validation et la persistance.
        if ($this->filled('endorsement_type') && ! $this->filled('creation_mode')) {
            $this->merge(['creation_mode' => 'endorsement']);
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
            'creation_mode' => ['nullable', 'string', 'in:renewal,endorsement'],
            'endorsement_type' => ['nullable', 'string', 'required_if:creation_mode,endorsement', 'in:registration_change,vehicle_info_update,client_info_update,other'],
            'status' => ['nullable', 'string', 'in:draft,validated,active,cancelled,expired'],
            'start_date' => [
                'required',
                'date',
                Rule::when($this->input('creation_mode') === 'endorsement', ['after_or_equal:today']),
            ],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration' => ['nullable', 'string', 'in:1_month,3_months,6_months,12_months'],
            'reduction_amount' => ['nullable', 'integer', 'min:0'],
            'accessory_amount_override' => ['nullable', 'numeric', 'min:0'],
            'base_amount_override' => ['nullable', 'integer', 'min:0'],
            'rc_amount_override' => ['nullable', 'integer', 'min:0'],
            'defence_appeal_amount_override' => ['nullable', 'integer', 'min:0'],
            'person_transport_amount_override' => ['nullable', 'integer', 'min:0'],
            'taxes_amount_override' => ['nullable', 'integer', 'min:0'],
            'cedeao_amount_override' => ['nullable', 'integer', 'min:0'],
            'fga_amount_override' => ['nullable', 'integer', 'min:0'],
            'reduction_bns' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_amount' => ['nullable', 'integer', 'min:0'],
            'company_accessory' => ['nullable', 'integer', 'min:0'],
            'agency_accessory' => ['nullable', 'integer', 'min:0'],
            'commission_amount' => ['nullable', 'integer', 'min:0'],
            'optional_guarantees_amount' => ['nullable', 'integer', 'min:0'],
            'optional_guarantees_detail' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'endorsement_type' => 'type d\'avenant',
            'creation_mode' => 'mode de création',
        ];
    }

    public function messages(): array
    {
        return [
            'endorsement_type.required_if' => 'Le type d\'avenant est obligatoire lorsque le mode de création est « avenant ».',
            'start_date.after_or_equal' => 'La date d\'effet de l\'avenant ne peut pas être antérieure à la date du jour.',
        ];
    }
}
