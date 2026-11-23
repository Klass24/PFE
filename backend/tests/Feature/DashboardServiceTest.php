<?php

namespace Tests\Feature;

use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Document\Models\Document;
use App\Modules\Driver\Models\Driver;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Mission\Models\Mission;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    public function test_dashboard_reports_company_activity_costs_documents_and_leaderboard(): void
    {
        Cache::flush();
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'DASH-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
            'status' => 'available',
        ]);
        $driverUser = $this->createUser('driver', ['name' => 'Dashboard Driver']);
        $driver = Driver::create([
            'company_id' => $this->company->id,
            'user_id' => $driverUser->id,
            'license_number' => 'DASH-LIC',
            'license_expiry' => now()->addYear(),
            'status' => 'available',
        ]);
        $mission = Mission::create([
            'company_id' => $this->company->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'origin' => 'A',
            'destination' => 'B',
            'status' => 'completed',
            'start_date' => now()->subDay(),
            'total_cost' => 100,
        ]);
        Expense::create([
            'company_id' => $this->company->id,
            'mission_id' => $mission->id,
            'type' => 'fuel',
            'amount' => 50,
            'created_by' => $this->admin->id,
        ]);
        Incident::create([
            'company_id' => $this->company->id,
            'mission_id' => $mission->id,
            'type' => 'breakdown',
            'description' => 'Engine trouble',
        ]);
        Document::create([
            'company_id' => $this->company->id,
            'documentable_type' => Vehicle::class,
            'documentable_id' => $vehicle->id,
            'type' => 'insurance',
            'file_path' => 'insurance.pdf',
            'expiry_date' => now()->addDays(5),
        ]);

        $service = new DashboardService();
        $companyId = $this->company->id;
        $from = now()->subDays(2)->toDateString();
        $to = now()->addDay()->toDateString();
        $stats = $service->getStats($companyId);
        $this->assertSame(1, $stats['total_vehicles']);
        $this->assertSame(1, $stats['available_vehicles']);
        $this->assertSame(1, $stats['total_drivers']);
        $this->assertSame(50.0, $stats['total_expenses_month']);
        $this->assertSame(1, $stats['expiring_documents_30']);
        $this->assertSame(1, $stats['incidents_this_month']);

        $expenses = $service->getExpensesByVehicle($companyId, $from, $to);
        $this->assertCount(1, $expenses);
        $this->assertSame('DASH-001', $expenses[0]->plate_number);
        $this->assertEquals(50, $expenses[0]->total);
        $incidents = $service->getIncidentsByType($companyId, $from, $to);
        $this->assertSame('breakdown', $incidents[0]->type);
        $this->assertSame(1, $incidents[0]->count);
        $missions = $service->getCostPerMission($companyId, $from, $to);
        $this->assertCount(1, $missions);
        $this->assertEquals(100, $missions[0]->total_cost);
        $documents = $service->getExpiringDocuments($companyId);
        $this->assertCount(1, $documents);
        $this->assertSame('critical', $documents[0]['status']);
        $this->assertSame((string) $vehicle->uuid, (string) $documents[0]['entity_uuid']);
        $leaderboard = $service->getDriverLeaderboard($companyId);
        $this->assertSame('Dashboard Driver', $leaderboard['efficiency'][0]->name);
        $this->assertEquals(100, $leaderboard['efficiency'][0]->avg_cost);
        $this->assertSame(1, $leaderboard['safety'][0]->incidents_count);

        $this->getJson('/api/v1/dashboard/stats')->assertOk()->assertJsonPath('data.total_vehicles', 1);
        foreach (['expenses-by-vehicle', 'incidents-by-type', 'cost-per-mission', 'expiring-documents'] as $endpoint) {
            $this->getJson('/api/v1/dashboard/' . $endpoint)->assertOk();
        }
        $this->getJson('/api/v1/dashboard/driver-leaderboard')->assertOk()->assertJsonPath('data.safety.0.incidents_count', 1);
        $this->getJson('/api/v1/dashboard/expenses-by-vehicle?from=2025-02-01&to=2025-01-01')->assertUnprocessable();
    }
}
