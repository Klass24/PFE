<?php

namespace Tests\Feature;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    private function vehicleData(array $overrides = []): array
    {
        return array_merge([
            'plate_number' => 'AB-' . rand(100, 999) . '-CD',
            'brand'        => 'Toyota',
            'model'        => 'Hilux',
            'year'         => 2024,
            'type'         => 'truck',
            'fuel_type'    => 'diesel',
            'mileage'      => 15000,
        ], $overrides);
    }

    public function test_admin_can_list_vehicles(): void
    {
        Vehicle::create(array_merge($this->vehicleData(), [
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
        ]));

        $response = $this->actingAsAdmin()->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_vehicle(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/vehicles', $this->vehicleData());

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vehicles', [
            'company_id'   => $this->company->id,
            'plate_number' => $response->json('data.plate_number'),
        ]);
    }

    public function test_admin_can_show_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->vehicleData(), [
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
        ]));

        $response = $this->actingAsAdmin()
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_update_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->vehicleData(), [
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
        ]));

        $response = $this->actingAsAdmin()
            ->putJson("/api/v1/vehicles/{$vehicle->uuid}", [
                'mileage' => 20000,
            ]);

        $response->assertOk();
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->vehicleData(), [
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
        ]));

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/v1/vehicles/{$vehicle->uuid}");

        $response->assertOk();
    }

    public function test_driver_cannot_create_vehicle(): void
    {
        $driver = $this->createUser('driver');

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->vehicleData());

        $response->assertStatus(403);
    }

    public function test_create_vehicle_validation_errors(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/vehicles', []);

        $response->assertStatus(422);
    }

    public function test_tenant_isolation_vehicles(): void
    {
        $otherCompany = $this->createCompany(['email' => 'other@company.com']);
        Vehicle::create(array_merge($this->vehicleData(), [
            'uuid'       => Str::uuid(),
            'company_id' => $otherCompany->id,
        ]));

        $response = $this->actingAsAdmin()->getJson('/api/v1/vehicles');

        $response->assertOk();
        // Should not see the vehicle from the other company
        $this->assertCount(0, $response->json('data'));
    }
}
