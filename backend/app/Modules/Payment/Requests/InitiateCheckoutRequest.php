<?php

namespace App\Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'plan_slug'     => 'required|string|exists:plans,slug',
            'gateway'       => 'required|in:stripe,paypal',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
        ];
    }

    public function messages(): array
    {
        return [
            'plan_slug.required' => 'Le plan est obligatoire.',
            'plan_slug.exists'   => 'Ce plan n\'existe pas.',
            'gateway.required'   => 'La passerelle de paiement est obligatoire.',
            'gateway.in'         => 'La passerelle doit être stripe ou paypal.',
            'billing_cycle.in'   => 'Le cycle doit être monthly ou yearly.',
        ];
    }
}
