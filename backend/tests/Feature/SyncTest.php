<?php

namespace Tests\Feature;

use Tests\TestCase;

class SyncTest extends TestCase
{
    public function test_sync_endpoint_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/sync', []);

        $response->assertStatus(401);
    }

    public function test_sync_with_empty_operations(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/sync', [
                'device_id'    => 'test-device-001',
                'last_sync_at' => null,
                'operations'   => [],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_sync_creates_expense(): void
    {
        // Create required mission first
        $vehicle = \App\Modules\Vehicle\Models\Vehicle::create([
            'uuid'         => \Illuminate\Support\Str::uuid(),
            'company_id'   => $this->company->id,
            'plate_number' => 'SYNC-001',
            'brand'        => 'Toyota',
            'model'        => 'Corolla',
            'year'         => 2024,
            'type'         => 'car',
            'fuel_type'    => 'gasoline',
        ]);

        $driverUser = $this->createUser('driver');
        $driver = \App\Modules\Driver\Models\Driver::create([
            'uuid'           => \Illuminate\Support\Str::uuid(),
            'company_id'     => $this->company->id,
            'user_id'        => $driverUser->id,
            'license_number' => 'SYNC-LIC',
            'license_expiry' => now()->addYear(),
            'phone'          => '+242060000099',
            'status'         => 'available',
        ]);

        $mission = \App\Modules\Mission\Models\Mission::create([
            'uuid'        => \Illuminate\Support\Str::uuid(),
            'company_id'  => $this->company->id,
            'vehicle_id'  => $vehicle->id,
            'driver_id'   => $driver->id,
            'origin'      => 'Brazzaville',
            'destination' => 'Dolisie',
            'status'      => 'in_progress',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/sync', [
                'device_id'    => 'test-device-002',
                'last_sync_at' => null,
                'operations'   => [
                    [
                        'local_uuid'       => \Illuminate\Support\Str::uuid()->toString(),
                        'type'             => 'expense',
                        'action'           => 'create',
                        'data'             => [
                            'amount'       => 3500,
                            'type'         => 'fuel',
                            'mission_uuid' => (string) $mission->uuid,
                            'liters'       => 30,
                        ],
                        'client_created_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.synced_count', 1)
            ->assertJsonPath('data.skipped_count', 0);

        $this->assertDatabaseHas('expenses', [
            'mission_id' => $mission->id,
            'company_id' => $this->company->id,
            'amount' => 3500,
        ]);

        $incidentUuid = (string) \Illuminate\Support\Str::uuid();
        $operation = [
            'local_uuid' => $incidentUuid,
            'type' => 'incident',
            'action' => 'create',
            'data' => ['mission_uuid' => (string) $mission->uuid, 'type' => 'breakdown', 'description' => 'Panne'],
            'client_created_at' => now()->toIso8601String(),
        ];
        $this->postJson('/api/v1/sync', ['device_id' => 'incident-device', 'operations' => [$operation]])
            ->assertOk()->assertJsonPath('data.synced_count', 1);
        $this->assertDatabaseHas('incidents', [
            'uuid' => $incidentUuid,
            'mission_id' => $mission->id,
            'company_id' => $this->company->id,
        ]);
        $operation['action'] = 'update';
        $operation['data']['description'] = 'Repaired';
        $this->postJson('/api/v1/sync', ['device_id' => 'incident-device', 'operations' => [$operation]])
            ->assertOk()->assertJsonPath('data.synced_count', 1);
        $this->assertDatabaseHas('incidents', ['uuid' => $incidentUuid, 'description' => 'Repaired']);
        $operation['action'] = 'delete';
        unset($operation['data']);
        $this->postJson('/api/v1/sync', ['device_id' => 'incident-device', 'operations' => [$operation]])
            ->assertOk()->assertJsonPath('data.synced_count', 1);
        $this->assertDatabaseMissing('incidents', ['uuid' => $incidentUuid]);
    }

    public function test_sync_reports_failed_operations_without_discarding_successful_operations(): void
    {
        $successUuid = (string) \Illuminate\Support\Str::uuid();
        $failureUuid = (string) \Illuminate\Support\Str::uuid();

        $response = $this->actingAsAdmin()->postJson('/api/v1/sync', [
            'device_id' => 'partial-device',
            'operations' => [
                [
                    'local_uuid' => $successUuid,
                    'type' => 'expense',
                    'action' => 'delete',
                    'client_created_at' => now()->toIso8601String(),
                ],
                [
                    'local_uuid' => $failureUuid,
                    'type' => 'checklist',
                    'action' => 'create',
                    'data' => ['mission_uuid' => (string) \Illuminate\Support\Str::uuid()],
                    'client_created_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.synced_count', 1)
            ->assertJsonPath('data.skipped_count', 1)
            ->assertJsonPath('data.errors.0.local_uuid', $failureUuid)
            ->assertJsonPath('data.errors.0.error', 'Mission introuvable pour la checklist.');
        $this->assertDatabaseHas('sync_logs', [
            'device_id' => 'partial-device',
            'company_id' => $this->company->id,
            'status' => 'partial',
            'payload_count' => 2,
        ]);
    }

    public function test_sync_rejects_invalid_operation_types(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/v1/sync', [
            'device_id' => 'invalid-device',
            'operations' => [[
                'local_uuid' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'unknown',
                'action' => 'create',
                'data' => [],
                'client_created_at' => now()->toIso8601String(),
            ]],
        ]);

        $response->assertUnprocessable();
        $this->assertSame(
            'Type d\'opération invalide.',
            $response->json('data')['operations.0.type'][0]
        );

        $this->assertDatabaseMissing('sync_logs', ['device_id' => 'invalid-device']);
    }

    public function test_sync_returns_an_empty_mission_change_list_when_no_missions_exist(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/v1/sync', [
            'device_id' => 'changes-device',
            'last_sync_at' => now()->subDay()->toIso8601String(),
            'operations' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.synced_count', 0)
            ->assertJsonPath('data.changes.missions', []);
        $this->assertDatabaseHas('sync_logs', [
            'device_id' => 'changes-device',
            'status' => 'success',
            'payload_count' => 0,
        ]);
    }
}
