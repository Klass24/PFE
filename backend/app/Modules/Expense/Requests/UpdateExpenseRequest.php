<?php

namespace App\Modules\Expense\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'type'    => 'sometimes|in:fuel,toll,repair,parking,other',
            'amount'  => 'sometimes|numeric|min:0.01',
            'liters'  => 'sometimes|nullable|numeric|min:0',
            'mileage' => 'sometimes|nullable|integer|min:0',
            'notes'   => 'sometimes|nullable|string|max:500',
        ];
    }
}
