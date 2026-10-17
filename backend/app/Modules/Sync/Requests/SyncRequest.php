<?php

namespace App\Modules\Sync\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'device_id'                     => 'required|string|max:100',
            'last_sync_at'                  => 'nullable|date',
            'operations'                    => 'present|array|max:500',
            'operations.*.local_uuid'       => 'required|uuid',
            'operations.*.type'             => 'required|in:expense,incident,checklist',
            'operations.*.action'           => 'required|in:create,update,delete',
            'operations.*.data'             => 'required_unless:operations.*.action,delete|array',
            'operations.*.client_created_at'=> 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required'                   => "L'identifiant de l'appareil est obligatoire.",
            'operations.required'                  => 'Les opérations sont obligatoires.',
            'operations.*.local_uuid.required'     => "L'UUID local est obligatoire.",
            'operations.*.type.in'                 => 'Type d\'opération invalide.',
            'operations.*.action.in'               => 'Action invalide.',
        ];
    }
}
