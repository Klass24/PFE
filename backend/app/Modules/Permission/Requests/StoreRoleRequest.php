<?php

namespace App\Modules\Permission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:50|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Le nom du rôle est obligatoire.',
            'name.unique'         => 'Ce rôle existe déjà.',
            'name.max'            => 'Le nom du rôle ne doit pas dépasser 50 caractères.',
            'permissions.array'   => 'Les permissions doivent être un tableau.',
            'permissions.*.exists' => 'Une des permissions sélectionnées est invalide.',
        ];
    }
}
