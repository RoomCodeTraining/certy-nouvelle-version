<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $keys = [
            'circulation_zone_id', 'energy_source_id', 'vehicle_usage_id', 'vehicle_type_id',
            'vehicle_category_id', 'vehicle_gender_id', 'color_id', 'fiscal_power', 'year_of_first_registration',
            'payload_capacity', 'engine_capacity', 'seat_count', 'new_value', 'replacement_value',
        ];
        $this->merge(array_map(fn ($v) => $v === '' ? null : $v, $this->only($keys)));
    }

    public function rules(): array
    {
        $type = $this->input('pricing_type');

        return [
            'client_id' => ['required', 'exists:clients,id'],
            'pricing_type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'vehicle_model_id' => ['required', 'exists:vehicle_models,id'],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'registration_number' => ['required', 'string', 'max:50'],
            'body_type' => ['required', 'string', 'max:100'],
            'circulation_zone_id' => ['nullable', 'exists:circulation_zones,id'],
            'energy_source_id' => [Rule::requiredIf($type === 'VP'), 'nullable', 'exists:energy_sources,id'],
            'vehicle_usage_id' => ['required', 'exists:vehicle_usages,id'],
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'vehicle_gender_id' => ['required', 'exists:vehicle_genders,id'],
            'color_id' => ['required', 'exists:colors,id'],
            'fiscal_power' => [Rule::requiredIf($type === 'VP'), 'nullable', 'integer', 'min:0'],
            'payload_capacity' => [Rule::requiredIf(in_array($type, ['TPC', 'TPM'], true)), 'nullable', 'numeric', 'min:0'],
            'engine_capacity' => [Rule::requiredIf($type === 'TWO_WHEELER'), 'nullable', 'integer', 'min:0'],
            'seat_count' => ['required', 'integer', 'min:0'],
            'year_of_first_registration' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'first_registration_date' => ['required', 'date'],
            'registration_card_number' => ['nullable', 'string', 'max:80'],
            'chassis_number' => ['nullable', 'string', 'max:80'],
            'new_value' => ['nullable', 'numeric', 'min:0'],
            'replacement_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
