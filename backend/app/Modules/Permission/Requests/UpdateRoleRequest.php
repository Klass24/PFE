<?php

namespace App\Modules\Permission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name'          => ['sometimes', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'          => 'Ce nom de rôle est déjà utilisé.',
            'name.max'             => 'Le nom du rôle ne doit pas dépasser 50 caractères.',
            'permissions.array'    => 'Les permissions doivent être un tableau.',
            'permissions.*.exists' => 'Une des permissions sélectionnées est invalide.',
        ];
    }
}
