<?php

namespace App\Modules\Document\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Driver\Models\Driver;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'documentable_type' => 'required|in:vehicle,driver',
            'documentable_uuid' => 'required|string',
            'type'              => 'required|in:insurance,registration,license,technical_control,other',
            'file'              => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'expiry_date'       => 'nullable|date',
            'issue_date'        => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'              => 'Le fichier est obligatoire.',
            'type.required'              => 'Le type de document est obligatoire.',
            'documentable_type.required' => 'Le type de ressource est obligatoire.',
            'documentable_type.in'       => 'Le type doit être "vehicle" ou "driver".',
            'documentable_uuid.required' => 'L\'identifiant de la ressource est obligatoire.',
        ];
    }

    /**
     * Surcharge validated() pour transformer type + uuid → classe + id
     * après que la validation ait réussi.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $map = [
            'vehicle' => Vehicle::class,
            'driver'  => Driver::class,
        ];

        $entityClass = $map[$data['documentable_type']];
        $model = $entityClass::where('uuid', $data['documentable_uuid'])->firstOrFail();

        $data['documentable_type'] = $entityClass;
        $data['documentable_id']   = $model->id;
        unset($data['documentable_uuid']);

        return $data;
    }
}

