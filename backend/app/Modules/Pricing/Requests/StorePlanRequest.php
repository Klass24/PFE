<?php

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:50',
            'slug'          => 'required|string|max:50|unique:plans,slug',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'currency'      => 'sometimes|string|max:5',
            'max_vehicles'  => 'required|integer|min:1',
            'max_drivers'   => 'required|integer|min:1',
            'max_users'     => 'required|integer|min:1',
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
            'name.required'          => 'Le nom du plan est obligatoire.',
            'slug.required'          => 'Le slug est obligatoire.',
            'slug.unique'            => 'Ce slug est déjà utilisé.',
            'price_monthly.required' => 'Le prix mensuel est obligatoire.',
            'price_yearly.required'  => 'Le prix annuel est obligatoire.',
            'max_vehicles.required'  => 'Le nombre max de véhicules est obligatoire.',
            'max_drivers.required'   => 'Le nombre max de chauffeurs est obligatoire.',
            'max_users.required'     => 'Le nombre max d\'utilisateurs est obligatoire.',
        ];
    }
}
