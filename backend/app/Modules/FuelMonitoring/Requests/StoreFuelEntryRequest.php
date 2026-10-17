<?php

namespace App\Modules\FuelMonitoring\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelEntryRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'vehicle_uuid'     => 'required|exists:vehicles,uuid',
            'liters'           => 'required|numeric|min:0.1|max:9999',
            'cost_per_liter'   => 'required|numeric|min:0.01',
            'odometer_reading' => 'required|integer|min:0',
            'station_name'     => 'nullable|string|max:255',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'recorded_at'      => 'nullable|date|before_or_equal:now',
            'mission_id'       => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_uuid.required'     => 'Le véhicule est obligatoire.',
            'liters.required'           => 'La quantité en litres est obligatoire.',
            'cost_per_liter.required'   => 'Le prix au litre est obligatoire.',
            'odometer_reading.required' => 'Le kilométrage est obligatoire.',
        ];
    }
}
