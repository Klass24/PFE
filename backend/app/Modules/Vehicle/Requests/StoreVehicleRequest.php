<?php

namespace App\Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'chassis_number'        => 'sometimes|string|max:100',
            'brand'        => 'required|string|max:100',
            'model'        => 'required|string|max:100',
            'year'         => 'required|integer|min:1990|max:' . now()->year + 1,
            'mileage'      => 'nullable|integer|min:0',
            'fuel_type'    => 'required|in:gasoline,diesel,electric,hybrid',
            'status'       => 'nullable|in:available,on_mission,maintenance,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.required' => "L'immatriculation est obligatoire.",
            'plate_number.unique'   => "Ce numéro d'immatriculation est déjà utilisé.",
            'brand.required'        => 'La marque est obligatoire.',
            'model.required'        => 'Le modèle est obligatoire.',
            'year.required'         => "L'année est obligatoire.",
            'fuel_type.required'    => 'Le type de carburant est obligatoire.',
            'fuel_type.in'          => 'Le type de carburant est invalide.',
        ];
    }
}
