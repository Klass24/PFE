<?php

namespace App\Modules\Incident\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'type'                => 'required|in:accident,breakdown,flat_tire,traffic,police_check,mechanical_issue,other',
            'description'         => 'required|string|max:2000',
            'latitude'            => 'nullable|numeric|between:-90,90',
            'longitude'           => 'nullable|numeric|between:-180,180',
            'image'               => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'device_id'           => 'nullable|string|max:100',
            'reported_at'         => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'        => "Le type d'incident est obligatoire.",
            'description.required' => 'La description est obligatoire.',
        ];
    }
}
