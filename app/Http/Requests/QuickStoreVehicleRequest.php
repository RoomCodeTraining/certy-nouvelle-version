<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'pricing_type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'registration_number' => ['required', 'string', 'max:50'],
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'vehicle_model_id' => ['required', 'exists:vehicle_models,id'],
        ];
    }
}
