<?php

namespace App\Modules\Sync\Services;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncService
{
    public function process(array $payload, User $user): array
    {
        $deviceId     = $payload['device_id'];
        $lastSyncAt   = $payload['last_sync_at'] ?? null;
        $operations   = $payload['operations'];
        $receivedAt   = now();

        $synced  = 0;
        $skipped = 0;
        $errors  = [];

        DB::transaction(function () use ($operations, $user, $receivedAt, &$synced, &$skipped, &$errors) {
            foreach ($operations as $op) {
                try {
                    $this->processOperation($op, $user, $receivedAt);
                    $synced++;
                } catch (\Throwable $e) {
                    Log::warning('Sync operation failed', [
                        'local_uuid' => $op['local_uuid'],
                        'error'      => $e->getMessage(),
                    ]);
                    $errors[] = ['local_uuid' => $op['local_uuid'], 'error' => $e->getMessage()];
                    $skipped++;
                }
            }
        });

        // Enregistrer le log de sync
        \DB::table('sync_logs')->insert([
            'device_id'     => $deviceId,
            'company_id'    => $user->company_id,
            'last_sync_at'  => $receivedAt,
            'status'        => $skipped > 0 ? ($synced > 0 ? 'partial' : 'failed') : 'success',
            'payload_count' => count($operations),
            'error_message' => $skipped > 0 ? json_encode($errors) : null,
            'created_at'    => $receivedAt,
        ]);

        // Retourner les données modifiées depuis la dernière sync
        $changes = $this->getChangesSince($lastSyncAt, $user);

        Log::info('Sync completed', [
            'device_id' => $deviceId,
            'synced'    => $synced,
            'skipped'   => $skipped,
        ]);

        return [
            'synced_count'  => $synced,
            'skipped_count' => $skipped,
            'errors'        => $errors,
            'server_time'   => $receivedAt->toIso8601String(),
            'changes'       => $changes,
        ];
    }

    private function processOperation(array $op, User $user, \Carbon\Carbon $receivedAt): void
    {
        match ($op['type']) {
            'expense'   => $this->syncExpense($op, $user, $receivedAt),
            'incident'  => $this->syncIncident($op, $user, $receivedAt),
            'checklist' => $this->syncChecklist($op, $user, $receivedAt),
        };
    }

    private function syncExpense(array $op, User $user, \Carbon\Carbon $receivedAt): void
    {
        if ($op['action'] === 'delete') {
            Expense::withoutGlobalScopes()->where('uuid', $op['local_uuid'])->where('company_id', $user->company_id)->delete();
            return;
        }

        $data = array_merge($op['data'], [
            'uuid'       => $op['local_uuid'],
            'company_id' => $user->company_id,
            'created_by' => $user->id,
        ]);

        // Résoudre le mission_id depuis uuid si fourni
        if (!empty($data['mission_uuid'])) {
            $mission = \App\Modules\Mission\Models\Mission::withoutGlobalScopes()
                ->where('uuid', $data['mission_uuid'])
                ->where('company_id', $user->company_id)
                ->first();
            if ($mission) {
                $data['mission_id'] = $mission->id;
            }
            unset($data['mission_uuid']);
        }

        Expense::withoutGlobalScopes()->upsert(
            [$data],
            ['uuid'],
            ['type', 'amount', 'liters', 'mileage', 'notes', 'updated_at']
        );
    }

    private function syncIncident(array $op, User $user, \Carbon\Carbon $receivedAt): void
    {
        if ($op['action'] === 'delete') {
            Incident::withoutGlobalScopes()->where('uuid', $op['local_uuid'])->where('company_id', $user->company_id)->delete();
            return;
        }

        $data = array_merge($op['data'], [
            'uuid'       => $op['local_uuid'],
            'company_id' => $user->company_id,
        ]);

        if (!empty($data['mission_uuid'])) {
            $mission = \App\Modules\Mission\Models\Mission::withoutGlobalScopes()
                ->where('uuid', $data['mission_uuid'])
                ->where('company_id', $user->company_id)
                ->first();
            if ($mission) {
                $data['mission_id'] = $mission->id;
            }
            unset($data['mission_uuid']);
        }

        Incident::withoutGlobalScopes()->upsert(
            [$data],
            ['uuid'],
            ['type', 'description', 'latitude', 'longitude', 'updated_at']
        );
    }

    private function syncChecklist(array $op, User $user, \Carbon\Carbon $receivedAt): void
    {
        $data = $op['data'];

        // Résoudre la mission
        $mission = null;
        if (!empty($data['mission_uuid'])) {
            $mission = \App\Modules\Mission\Models\Mission::withoutGlobalScopes()
                ->where('uuid', $data['mission_uuid'])
                ->where('company_id', $user->company_id)
                ->first();
        }
        if (!$mission) {
            throw new \RuntimeException('Mission introuvable pour la checklist.');
        }

        // Trouver le driver lié à l'utilisateur
        $driver = \App\Modules\Driver\Models\Driver::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        // Upsert la checklist
        $checklist = Checklist::withoutGlobalScopes()->updateOrCreate(
            ['mission_id' => $mission->id],
            [
                'uuid'         => $op['local_uuid'],
                'driver_id'    => $driver?->id,
                'vehicle_id'   => $mission->vehicle_id,
                'submitted_at' => $data['submitted_at'] ?? $receivedAt,
                'status'       => $data['status'] ?? 'submitted',
                'sync_status'  => 'synced',
            ]
        );

        // Synchroniser les items
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $checklist->items()->updateOrCreate(
                    ['item_key' => $item['item_key']],
                    [
                        'is_ok'          => $item['is_ok'] ?? false,
                        'requires_photo' => $item['requires_photo'] ?? false,
                        'photo_path'     => $item['photo_path'] ?? null,
                        'note'           => $item['note'] ?? null,
                    ]
                );
            }
        }
    }

    private function getChangesSince(?string $lastSyncAt, User $user): array
    {
        if (!$lastSyncAt) {
            return [];
        }

        return [
            'missions' => \App\Modules\Mission\Models\Mission::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('updated_at', '>', $lastSyncAt)
                ->get(['uuid', 'status', 'updated_at']),
        ];
    }
}
