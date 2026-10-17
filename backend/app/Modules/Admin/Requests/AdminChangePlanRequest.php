<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'plan'     => 'required|in:starter,pro,enterprise',
            'end_date' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'plan.required'  => 'Le plan est obligatoire.',
            'plan.in'        => 'Le plan doit être starter, pro ou enterprise.',
            'end_date.date'  => 'La date de fin doit être une date valide.',
            'end_date.after' => 'La date de fin doit être dans le futur.',
        ];
    }
}
