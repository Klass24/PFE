<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'billing_status' => 'sometimes|in:active,cancelled,past_due,trial',
            'end_date'       => 'sometimes|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'billing_status.in' => 'Le statut doit être active, cancelled, past_due ou trial.',
            'end_date.date'     => 'La date de fin doit être une date valide.',
        ];
    }
}
