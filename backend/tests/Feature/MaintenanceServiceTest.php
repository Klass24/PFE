<?php

namespace Tests\Feature;

use App\Modules\Maintenance\Repositories\MaintenanceRepository;
use App\Modules\Maintenance\Services\MaintenanceService;
use App\Modules\Vehicle\Models\Vehicle;
use Tests\TestCase;

class MaintenanceServiceTest extends TestCase
{
    private Vehicle $vehicle;
    private MaintenanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'MAINT-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
            'mileage' => 10000,
        ]);
        $this->service = new MaintenanceService(new MaintenanceRepository());
    }

    public function test_create_calculates_due_date_and_mileage_and_can_be_updated_and_deleted(): void
    {
        $schedule = $this->service->create([
            'vehicle_uuid' => (string) $this->vehicle->uuid,
            'maintenance_type' => 'oil_change',
            'frequency_months' => 6,
            'frequency_km' => 5000,
            'last_service_date' => '2025-01-15',
            'last_service_km' => 10000,
        ]);

        $this->assertSame('2025-07-15', $schedule->next_due_date->toDateString());
        $this->assertSame(15000, $schedule->next_due_km);
        $this->assertSame($this->company->id, $schedule->company_id);
        $this->assertSame(1, $this->service->paginate(['vehicle_uuid' => $this->vehicle->uuid])->total());
        $this->assertSame('inspection', $this->service->update($schedule, ['maintenance_type' => 'inspection'])->maintenance_type);

        $this->service->delete($schedule);
        $this->assertDatabaseMissing('maintenance_schedules', ['id' => $schedule->id]);
    }

    public function test_completing_service_records_cost_and_updates_schedule_and_mileage(): void
    {
        $schedule = $this->service->create([
            'vehicle_uuid' => (string) $this->vehicle->uuid,
            'maintenance_type' => 'oil_change',
            'frequency_months' => 6,
            'frequency_km' => 5000,
        ]);

        $record = $this->service->completeService($schedule, [
            'service_date' => '2025-02-15',
            'service_km' => 12000,
            'cost' => 100,
            'provider' => 'Garage',
        ]);

        $this->assertSame($schedule->id, $record->maintenance_schedule_id);
        $this->assertSame('100.00', $record->cost);
        $this->assertSame($this->admin->id, $record->performed_by);
        $this->assertSame('2025-08-15', $schedule->fresh()->next_due_date->toDateString());
        $this->assertSame(17000, $schedule->fresh()->next_due_km);
        $this->assertSame(12000, $this->vehicle->fresh()->mileage);
    }

    public function test_finds_overdue_and_upcoming_schedules(): void
    {
        $overdue = $this->service->create([
            'vehicle_uuid' => (string) $this->vehicle->uuid,
            'maintenance_type' => 'oil_change',
            'next_due_date' => now()->subDay()->toDateString(),
        ]);
        $upcoming = $this->service->create([
            'vehicle_uuid' => (string) $this->vehicle->uuid,
            'maintenance_type' => 'inspection',
            'next_due_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame([$overdue->id], $this->service->getOverdueSchedules()->modelKeys());
        $this->assertSame([$upcoming->id], $this->service->getDueSoon()->modelKeys());
        $this->assertTrue($overdue->isOverdue());
        $this->assertTrue($upcoming->isDueSoon());
        $this->assertFalse($upcoming->isOverdue());
    }

    public function test_maintenance_api_create_list_complete_and_delete(): void
    {
        $response = $this->postJson('/api/v1/maintenance', [
            'vehicle_uuid' => (string) $this->vehicle->uuid,
            'maintenance_type' => 'oil_change',
            'frequency_km' => 5000,
            'frequency_months' => 6,
        ])->assertCreated();
        $uuid = $response->json('data.uuid');
        $this->getJson('/api/v1/maintenance')->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->getJson('/api/v1/maintenance/' . $uuid)->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->getJson('/api/v1/maintenance/alerts')->assertOk();
        $this->postJson('/api/v1/maintenance/' . $uuid . '/complete', [
            'service_date' => now()->toDateString(),
            'service_km' => 12000,
            'cost' => 100,
        ])->assertOk()->assertJsonPath('data.service_km', 12000);
        $this->deleteJson('/api/v1/maintenance/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('maintenance_schedules', ['uuid' => $uuid]);
        $this->postJson('/api/v1/maintenance', [])->assertUnprocessable();
    }
}
