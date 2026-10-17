<?php

namespace Tests\Feature;

use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use Illuminate\Support\Str;
use Tests\TestCase;

class MissionTest extends TestCase
{
    private Vehicle $vehicle;
    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehicle = Vehicle::create([
            'uuid'         => Str::uuid(),
            'company_id'   => $this->company->id,
            'plate_number' => 'TEST-001',
            'brand'        => 'Toyota',
            'model'        => 'Hilux',
            'year'         => 2024,
            'type'         => 'truck',
            'fuel_type'    => 'diesel',
        ]);

        $driverUser = $this->createUser('driver');
        $this->driver = Driver::create([
            'uuid'           => Str::uuid(),
            'company_id'     => $this->company->id,
            'user_id'        => $driverUser->id,
            'license_number' => 'LIC-' . rand(1000, 9999),
            'license_expiry' => now()->addYear(),
            'phone'          => '+242060000001',
            'status'         => 'active',
        ]);
    }

    private function missionData(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id'  => $this->vehicle->uuid,
            'driver_id'   => $this->driver->uuid,
            'origin'      => 'Brazzaville',
            'destination' => 'Pointe-Noire',
            'start_date'  => now()->addDay()->toDateTimeString(),
            'end_date'    => now()->addDays(2)->toDateTimeString(),
            'status'      => 'planned',
        ], $overrides);
    }

    public function test_admin_can_list_missions(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/missions');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_create_mission(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/missions', $this->missionData());

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_show_mission(): void
    {
        $mission = Mission::create(array_merge([
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id'  => $this->driver->id,
            'origin'      => 'Brazzaville',
            'destination' => 'Pointe-Noire',
            'status'      => 'planned',
        ]));

        $response = $this->actingAsAdmin()
            ->getJson("/api/v1/missions/{$mission->uuid}");

        $response->assertOk();
    }

    public function test_mission_start_changes_status(): void
    {
        $mission = Mission::create([
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id'  => $this->driver->id,
            'origin'      => 'Brazzaville',
            'destination' => 'Pointe-Noire',
            'status'      => 'planned',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/missions/{$mission->uuid}/start");

        $response->assertOk();
        $this->assertDatabaseHas('missions', [
            'id'     => $mission->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_mission_complete_changes_status(): void
    {
        $mission = Mission::create([
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id'  => $this->driver->id,
            'origin'      => 'Brazzaville',
            'destination' => 'Pointe-Noire',
            'status'      => 'in_progress',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/missions/{$mission->uuid}/complete");

        $response->assertOk();
        $this->assertDatabaseHas('missions', [
            'id'     => $mission->id,
            'status' => 'completed',
        ]);
    }

    public function test_driver_cannot_create_mission(): void
    {
        $driverUser = $this->createUser('driver');

        $response = $this->actingAs($driverUser, 'sanctum')
            ->postJson('/api/v1/missions', $this->missionData());

        $response->assertStatus(403);
    }

    public function test_tenant_isolation_missions(): void
    {
        $otherCompany = $this->createCompany(['email' => 'other2@company.com']);
        Mission::create([
            'uuid'       => Str::uuid(),
            'company_id' => $otherCompany->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id'  => $this->driver->id,
            'origin'      => 'Secret',
            'destination' => 'Hidden',
            'status'      => 'planned',
        ]);

        $response = $this->actingAsAdmin()->getJson('/api/v1/missions');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
