<?php

namespace App\Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'service_date' => 'required|date|before_or_equal:today',
            'service_km'   => 'required|integer|min:0',
            'cost'         => 'nullable|numeric|min:0',
            'provider'     => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'service_date.required' => 'La date de service est obligatoire.',
            'service_km.required'   => 'Le kilométrage est obligatoire.',
        ];
    }
}
