<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');
        $companyId = \App\Modules\Company\Models\Company::where('uuid', $uuid)->value('id');

        return [
            'name'    => 'sometimes|string|max:150',
            'email'   => ['sometimes', 'email', 'max:150', Rule::unique('companies', 'email')->ignore($companyId)],
            'phone'   => 'nullable|string|max:20',
            'plan'    => 'sometimes|in:starter,pro,enterprise',
            'status'  => 'sometimes|in:active,suspended',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'    => 'Le nom ne doit pas dépasser 150 caractères.',
            'email.unique' => 'Cet email est déjà utilisé par une autre entreprise.',
            'plan.in'     => 'Le plan doit être starter, pro ou enterprise.',
            'status.in'   => 'Le statut doit être active ou suspended.',
        ];
    }
}
