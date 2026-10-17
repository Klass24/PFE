<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:150',
            'company_email'=> 'required|email|unique:companies,email',
            'plan'         => 'sometimes|in:starter,pro,enterprise',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Le nom est obligatoire.',
            'email.required'         => "L'email est obligatoire.",
            'email.unique'           => 'Cet email est déjà utilisé.',
            'password.required'      => 'Le mot de passe est obligatoire.',
            'password.min'           => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'     => 'Les mots de passe ne correspondent pas.',
            'company_name.required'  => "Le nom de l'entreprise est obligatoire.",
            'company_email.required' => "L'email de l'entreprise est obligatoire.",
            'company_email.unique'   => "Cet email d'entreprise est déjà utilisé.",
            'plan.in'                => 'Le plan doit être starter, pro ou enterprise.',
        ];
    }
}
