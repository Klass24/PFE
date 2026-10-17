<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:150',
            'email'   => 'required|email|max:150|unique:companies,email',
            'phone'   => 'nullable|string|max:20',
            'plan'    => 'required|in:starter,pro,enterprise',
            'status'  => 'sometimes|in:active,suspended',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Le nom de l\'entreprise est obligatoire.',
            'name.max'       => 'Le nom ne doit pas dépasser 150 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique'   => 'Cet email est déjà utilisé par une autre entreprise.',
            'plan.required'  => 'Le plan est obligatoire.',
            'plan.in'        => 'Le plan doit être starter, pro ou enterprise.',
            'status.in'      => 'Le statut doit être active ou suspended.',
        ];
    }
}
