<?php

namespace App\Modules\Expense\Events;

use App\Modules\Expense\Models\Expense;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Expense $expense) {}
}
