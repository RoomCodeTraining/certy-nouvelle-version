<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'postal_address' => ['nullable', 'string'],
            'profession' => ['required', 'string', 'max:255'],
            'type_assure' => ['required', 'in:'.Client::TYPE_TAPP.','.Client::TYPE_TAPM],
        ];
    }
}
