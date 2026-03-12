<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractReductionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reduction_bns' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reduction_on_profession_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

