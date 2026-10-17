<?php

namespace App\Modules\Mission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'vehicle_id'  => 'sometimes|exists:vehicles,id',
            'driver_id'   => 'sometimes|exists:drivers,id',
            'origin'      => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'sometimes|nullable|date',
            'notes'       => 'sometimes|nullable|string|max:1000',
        ];
    }
}
