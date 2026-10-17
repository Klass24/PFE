<?php

namespace App\Modules\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'plan' => 'required|in:starter,pro,enterprise',
        ];
    }

    public function messages(): array
    {
        return [
            'plan.required' => 'Le plan est obligatoire.',
            'plan.in'       => 'Le plan doit être starter, pro ou enterprise.',
        ];
    }
}
