<?php

namespace App\Modules\ApiToken\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'abilities'  => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Le nom du token est obligatoire.',
            'expires_at.after' => 'La date d\'expiration doit être dans le futur.',
        ];
    }
}
