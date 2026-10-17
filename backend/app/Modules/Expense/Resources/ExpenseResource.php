<?php

namespace App\Modules\Expense\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'liters'         => $this->liters ? (float) $this->liters : null,
            'mileage'        => $this->mileage,
            'notes'          => $this->notes,
            'receipt_url'    => $this->receipt_image ? asset('storage/' . $this->receipt_image) : null,
            'mission_uuid'   => $this->mission?->uuid,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
