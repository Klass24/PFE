<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Expense\Events\ExpenseCreatedEvent;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class NotifyOnExpenseThresholdListener
{
    /** Seuil configurable (à mettre dans config/fleet.php si besoin). */
    private const THRESHOLD = 500.00;

    public function __construct(private NotificationService $service) {}

    public function handle(ExpenseCreatedEvent $event): void
    {
        $expense = $event->expense;

        if ((float) $expense->amount < self::THRESHOLD) {
            return;
        }

        $message = "Dépense inhabituelle de {$expense->amount} DH (type : {$expense->type}) "
            . "sur la mission #{$expense->mission_id}.";

        Log::warning('Expense above threshold', ['expense_id' => $expense->id, 'amount' => $expense->amount]);

        $this->service->notifyManagers($expense->company_id, 'expense_threshold', $message);
    }
}
