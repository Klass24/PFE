<?php

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id ?? null;

        return [
            'name'          => 'sometimes|string|max:50',
            'slug'          => 'sometimes|string|max:50|unique:plans,slug,' . $planId,
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly'  => 'sometimes|numeric|min:0',
            'currency'      => 'sometimes|string|max:5',
            'max_vehicles'  => 'sometimes|integer|min:1',
            'max_drivers'   => 'sometimes|integer|min:1',
            'max_users'     => 'sometimes|integer|min:1',
            'features'      => 'nullable|array',
            'features.*'    => 'string|max:200',
            'is_active'     => 'sometimes|boolean',
            'is_popular'    => 'sometimes|boolean',
            'sort_order'    => 'sometimes|integer|min:0',
            'trial_days'    => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Ce slug est déjà utilisé par un autre plan.',
        ];
    }
}
