<?php

namespace App\Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'vehicle_uuid'      => 'required|exists:vehicles,uuid',
            'maintenance_type'  => 'required|in:oil_change,tire_rotation,inspection,engine_service,brake_check,filter_change,other',
            'frequency_km'      => 'nullable|integer|min:100',
            'frequency_months'  => 'nullable|integer|min:1|max:60',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'last_service_km'   => 'nullable|integer|min:0',
            'notes'             => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_uuid.required' => 'Le véhicule est obligatoire.',
            'vehicle_uuid.exists'   => 'Véhicule introuvable.',
            'maintenance_type.in'   => 'Type de maintenance invalide.',
        ];
    }
}
