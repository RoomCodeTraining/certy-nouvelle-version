<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationCompanyConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        Log::info('Config request: prepareForValidation', ['all' => $this->all()]);
        $merge = [];
        if ($this->has('id') && $this->input('id') === '') {
            $merge['id'] = null;
        }
        if ($this->has('name') && $this->input('name') === '') {
            $merge['name'] = null;
        }
        if ($this->has('code') && $this->input('code') === '') {
            $merge['code'] = null;
        }
        if ($this->has('commission') && $this->input('commission') === '') {
            $merge['commission'] = null;
        }
        if ($this->has('policy_number_identifier') && $this->input('policy_number_identifier') === '') {
            $merge['policy_number_identifier'] = null;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:organization_company_configs,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'policy_number_identifier' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'code' => 'code',
            'commission' => 'commission',
            'policy_number_identifier' => 'identifiant numéro de police',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::info('Config request: validation échouée', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);
        throw new ValidationException($validator);
    }
}
