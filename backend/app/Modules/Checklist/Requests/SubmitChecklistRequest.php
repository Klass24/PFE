<?php

namespace App\Modules\Checklist\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'device_id'          => 'nullable|string|max:100',
            'date_saisie_reelle' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'date_saisie_reelle.date' => 'La date de saisie n\'est pas valide.',
        ];
    }
}
