<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'plan'           => 'required|in:starter,pro,enterprise',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'billing_status' => 'required|in:active,cancelled,past_due,trial',
        ];
    }

    public function messages(): array
    {
        return [
            'plan.required'           => 'Le plan est obligatoire.',
            'plan.in'                 => 'Le plan doit être starter, pro ou enterprise.',
            'start_date.required'     => 'La date de début est obligatoire.',
            'end_date.after'          => 'La date de fin doit être après la date de début.',
            'billing_status.required' => 'Le statut de facturation est obligatoire.',
            'billing_status.in'       => 'Le statut doit être active, cancelled, past_due ou trial.',
        ];
    }
}
