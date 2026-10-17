<?php

namespace App\Modules\Expense\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'type'                => 'required|in:fuel,toll,repair,parking,weighing,other',
            'amount'              => 'required|numeric|min:0.01',
            'liters'              => 'nullable|numeric|min:0',
            'mileage'             => 'nullable|integer|min:0',
            'notes'               => 'nullable|string|max:500',
            'device_id'           => 'nullable|string|max:100',
            'date_saisie_reelle'  => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'   => 'Le type de dépense est obligatoire.',
            'type.in'         => 'Type de dépense invalide.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min'      => 'Le montant doit être supérieur à 0.',
        ];
    }
}
