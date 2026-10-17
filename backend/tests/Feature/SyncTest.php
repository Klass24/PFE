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
                            'mission_id'   => $mission->uuid,
                            'liters'       => 30,
                        ],
                        'client_created_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}
