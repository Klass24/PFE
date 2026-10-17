<?php

namespace App\Modules\Mission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'vehicle_id'         => 'required|exists:vehicles,id',
            'driver_id'          => 'required|exists:drivers,id',
            'origin'             => 'required|string|max:255',
            'destination'        => 'required|string|max:255',
            'start_date'         => 'required|date',
            'end_date'           => 'nullable|date|after:start_date',
            'planned_departure'  => 'nullable|date',
            'planned_arrival'    => 'nullable|date|after:planned_departure',
            'notes'              => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required'  => 'Le véhicule est obligatoire.',
            'driver_id.required'   => 'Le chauffeur est obligatoire.',
            'origin.required'      => 'Le lieu de départ est obligatoire.',
            'destination.required' => 'La destination est obligatoire.',
            'start_date.required'  => 'La date de départ est obligatoire.',
            'end_date.after'       => 'La date de fin doit être après la date de départ.',
        ];
    }
}
