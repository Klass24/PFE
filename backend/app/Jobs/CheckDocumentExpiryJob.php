<?php

namespace App\Jobs;

use App\Modules\Document\Models\Document;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job planifié (cron quotidien) — vérifie les documents expirant dans 30j et 7j.
 * Schedule dans AppServiceProvider : Schedule::job(new CheckDocumentExpiryJob)->daily();
 */
class CheckDocumentExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $thresholds = [
            ['days' => 30, 'label' => '30 jours'],
            ['days' => 7,  'label' => '7 jours'],
        ];

        foreach ($thresholds as ['days' => $days, 'label' => $label]) {
            $targetDate = now()->addDays($days)->toDateString();

            // Uniquement les documents dont l'alerte n'a pas encore été envoyée
            Document::withoutGlobalScopes()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', $targetDate)
                ->where('alert_sent', false)
                ->with('documentable')
                ->chunk(100, function ($documents) use ($notificationService, $label) {
                    foreach ($documents as $doc) {
                        $entityName = $this->resolveEntityName($doc);
                        $message    = "Document \"{$doc->type}\" de {$entityName} expire dans {$label} (le {$doc->expiry_date->format('d/m/Y')}).";

                        $notificationService->notifyManagers(
                            $doc->company_id,
                            'document_expiring',
                            $message
                        );

                        $doc->update(['alert_sent' => true]);

                        Log::info('Document expiry alert sent', [
                            'document_id' => $doc->id,
                            'expiry_date' => $doc->expiry_date,
                        ]);
                    }
                });
        }
    }

    private function resolveEntityName(Document $doc): string
    {
        $entity = $doc->documentable;

        if (!$entity) {
            return 'entité inconnue';
        }

        return match (class_basename($entity)) {
            'Vehicle' => $entity->plate_number ?? 'véhicule',
            'Driver'  => $entity->user?->name ?? 'chauffeur',
            default   => class_basename($entity),
        };
    }
}
