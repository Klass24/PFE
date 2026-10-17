<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = optional(\App\Modules\User\Models\User::where('uuid', $this->route('uuid'))->first())->id;

        return [
            'name'      => 'sometimes|string|max:255',
            'email'     => "sometimes|email|max:255|unique:users,email,{$userId}",
            'password'  => 'sometimes|string|min:8|confirmed',
            'role'      => 'sometimes|in:admin,manager,driver',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Cet email est déjà utilisé.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in'            => 'Le rôle doit être admin, manager ou driver.',
        ];
    }
}
