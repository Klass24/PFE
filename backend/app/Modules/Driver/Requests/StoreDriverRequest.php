<?php

namespace App\Modules\Driver\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'user_id'        => 'nullable|exists:users,id',
            'user'           => 'nullable|array|required_without:user_id',
            'user.name'      => 'required_without:user_id|string|max:255',
            'user.email'     => 'required_without:user_id|email|max:255|unique:users,email',
            'user.password'  => 'required_without:user_id|string|min:8|confirmed',
            'user.password_confirmation' => 'required_without:user_id|string|min:8',
            'user.is_active' => 'nullable|boolean',
            'permissions'    => 'nullable|array',
            'permissions.*'  => 'string|exists:permissions,name',
            'license_number' => 'required|string|max:50|unique:drivers,license_number',
            'license_expiry' => 'required|date|after:today',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'nullable|in:available,on_mission,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'          => "L'utilisateur sélectionné n'existe pas.",
            'user.required_without'   => "Les informations utilisateur sont obligatoires.",
            'user.name.required_without' => 'Le nom utilisateur est obligatoire.',
            'user.email.required_without' => 'L\'email utilisateur est obligatoire.',
            'user.email.unique'       => 'Cet email utilisateur est déjà utilisé.',
            'user.password.required_without' => 'Le mot de passe utilisateur est obligatoire.',
            'user.password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'license_number.required' => 'Le numéro de permis est obligatoire.',
            'license_number.unique'   => 'Ce numéro de permis est déjà utilisé.',
            'license_expiry.required' => "La date d'expiration du permis est obligatoire.",
            'license_expiry.after'    => "La date d'expiration doit être dans le futur.",
        ];
    }
}
