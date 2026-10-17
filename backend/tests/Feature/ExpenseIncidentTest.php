<?php

namespace Tests\Feature;

use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpenseIncidentTest extends TestCase
{
    private Mission $mission;

    protected function setUp(): void
    {
        parent::setUp();

        $vehicle = Vehicle::create([
            'uuid'         => Str::uuid(),
            'company_id'   => $this->company->id,
            'plate_number' => 'EXP-001',
            'brand'        => 'Ford',
            'model'        => 'Transit',
            'year'         => 2023,
            'type'         => 'van',
            'fuel_type'    => 'diesel',
        ]);

        $driverUser = $this->createUser('driver');
        $driver = Driver::create([
            'uuid'           => Str::uuid(),
            'company_id'     => $this->company->id,
            'user_id'        => $driverUser->id,
            'license_number' => 'EXP-LIC-001',
            'license_expiry' => now()->addYear(),
            'phone'          => '+242060000002',
            'status'         => 'available',
        ]);

        $this->mission = Mission::create([
            'uuid'        => Str::uuid(),
            'company_id'  => $this->company->id,
            'vehicle_id'  => $vehicle->id,
            'driver_id'   => $driver->id,
            'origin'      => 'Brazzaville',
            'destination' => 'Pointe-Noire',
            'status'      => 'in_progress',
        ]);
    }

    public function test_admin_can_list_expenses(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/expenses');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_create_expense(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/missions/{$this->mission->uuid}/expenses", [
                'type'       => 'fuel',
                'amount'     => 5000.00,
                'liters'     => 45.5,
                'mileage'    => 120000,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_create_expense_validation(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/missions/{$this->mission->uuid}/expenses", []);

        $response->assertStatus(422);
    }
}

class IncidentTest extends TestCase
{
    private Mission $mission;

    protected function setUp(): void
    {
        parent::setUp();

        $vehicle = Vehicle::create([
            'uuid'         => Str::uuid(),
            'company_id'   => $this->company->id,
            'plate_number' => 'INC-001',
            'brand'        => 'Renault',
            'model'        => 'Master',
            'year'         => 2022,
            'type'         => 'van',
            'fuel_type'    => 'diesel',
        ]);

        $driverUser = $this->createUser('driver');
        $driver = Driver::create([
            'uuid'           => Str::uuid(),
            'company_id'     => $this->company->id,
            'user_id'        => $driverUser->id,
            'license_number' => 'INC-LIC-001',
            'license_expiry' => now()->addYear(),
            'phone'          => '+242060000003',
            'status'         => 'available',
        ]);

        $this->mission = Mission::create([
            'uuid'        => Str::uuid(),
            'company_id'  => $this->company->id,
            'vehicle_id'  => $vehicle->id,
            'driver_id'   => $driver->id,
            'origin'      => 'Pointe-Noire',
            'destination' => 'Nkayi',
            'status'      => 'in_progress',
        ]);
    }

    public function test_admin_can_list_incidents(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/incidents');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_report_incident(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/incidents', [
                'mission_id'  => $this->mission->uuid,
                'type'        => 'breakdown',
                'description' => 'Panne moteur sur autoroute',
                'latitude'    => -4.2634,
                'longitude'   => 15.2429,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }
}
