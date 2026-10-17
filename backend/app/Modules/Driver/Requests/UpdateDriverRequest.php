<?php

namespace App\Modules\Driver\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $driverId = optional(\App\Modules\Driver\Models\Driver::where('uuid', $this->route('uuid'))->first())->id;

        return [
            'license_number' => "sometimes|string|max:50|unique:drivers,license_number,{$driverId}",
            'license_expiry' => 'sometimes|date',
            'phone'          => 'sometimes|nullable|string|max:20',
            'status'         => 'sometimes|in:available,on_mission,inactive',
        ];
    }
}
