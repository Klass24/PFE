<?php

namespace App\Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $vehicleId = optional(\App\Modules\Vehicle\Models\Vehicle::where('uuid', $this->route('uuid'))->first())->id;

        return [
            'plate_number' => "sometimes|string|max:20|unique:vehicles,plate_number,{$vehicleId}",
            'chassis_number'        => 'sometimes|string|max:100',
            'brand'        => 'sometimes|string|max:100',
            'model'        => 'sometimes|string|max:100',
            'year'         => 'sometimes|integer|min:1990|max:' . now()->year + 1,
            'mileage'      => 'sometimes|integer|min:0',
            'fuel_type'    => 'sometimes|in:gasoline,diesel,electric,hybrid',
            'status'       => 'sometimes|in:available,on_mission,maintenance,inactive',
        ];
    }
}
