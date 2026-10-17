<?php

namespace App\Modules\Checklist\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'required|integer|exists:checklist_items,id',
            'items.*.is_ok'   => 'required|boolean',
            'items.*.note'    => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'         => 'La liste des items est requise.',
            'items.*.id.exists'      => 'Item de check-list introuvable.',
            'items.*.is_ok.required' => 'Le statut de chaque item est obligatoire.',
        ];
    }
}
