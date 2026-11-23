<?php

namespace Tests\Feature;

use App\Modules\Document\Models\Document;
use App\Modules\Driver\Models\Driver;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Mission\Models\Mission;
use App\Modules\Vehicle\Models\Vehicle;
use Tests\TestCase;

class ExportTest extends TestCase
{
    public function test_csv_exports_include_matching_records_and_respect_filters(): void
    {
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'EXPORT-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
        ]);
        $driverUser = $this->createUser('driver', ['name' => 'Export Driver']);
        $driver = Driver::create([
            'company_id' => $this->company->id,
            'user_id' => $driverUser->id,
            'license_number' => 'EXPORT-LIC',
            'license_expiry' => now()->addYear(),
            'status' => 'available',
        ]);
        $mission = Mission::create([
            'company_id' => $this->company->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'origin' => 'Brazzaville',
            'destination' => 'Dolisie',
            'status' => 'planned',
        ]);
        Expense::create([
            'company_id' => $this->company->id,
            'mission_id' => $mission->id,
            'type' => 'fuel',
            'amount' => 250,
            'created_by' => $this->admin->id,
        ]);
        Incident::create([
            'company_id' => $this->company->id,
            'mission_id' => $mission->id,
            'type' => 'breakdown',
            'description' => 'Exported incident',
        ]);
        Document::create([
            'company_id' => $this->company->id,
            'documentable_type' => Vehicle::class,
            'documentable_id' => $vehicle->id,
            'type' => 'insurance',
            'file_path' => 'insurance.pdf',
            'expiry_date' => now()->addDays(10),
        ]);
        MaintenanceSchedule::create([
            'company_id' => $this->company->id,
            'vehicle_id' => $vehicle->id,
            'maintenance_type' => 'oil_change',
            'status' => 'active',
        ]);

        $cases = [
            ['/api/v1/export/vehicles?brand=Toyota&type=truck', 'EXPORT-001'],
            ['/api/v1/export/drivers?name=Export&license_number=EXPORT', 'EXPORT-LIC'],
            ['/api/v1/export/missions?origin=Brazzaville&status=planned', 'Dolisie'],
            ['/api/v1/export/expenses?type=fuel', '250.00'],
            ['/api/v1/export/incidents?type=breakdown&description=Exported', 'Exported incident'],
            ['/api/v1/export/documents?status=expiring&type=insurance', 'insurance.pdf'],
            ['/api/v1/export/maintenance?maintenance_type=oil_change&vehicle_search=EXPORT', 'EXPORT-001'],
            ['/api/v1/export/users?role=driver&status=active', 'Export Driver'],
        ];

        foreach ($cases as [$url, $expected]) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
            $this->assertStringContainsString($expected, $response->streamedContent());
        }

        $response = $this->get('/api/v1/export/vehicles?brand=NotFound');
        $response->assertOk();
        $this->assertStringNotContainsString('EXPORT-001', $response->streamedContent());

        foreach (['expired', 'valid'] as $status) {
            $response = $this->get('/api/v1/export/documents?status=' . $status);
            $response->assertOk();
            $this->assertStringNotContainsString('insurance.pdf', $response->streamedContent());
        }
        $today = now()->toDateString();
        $filtered = [
            '/api/v1/export/vehicles?plate_number=EXPORT&model=Hilux&fuel_type=diesel&from=' . $today . '&to=' . $today,
            '/api/v1/export/drivers?email=driver&status=available&from=' . $today . '&to=' . $today,
            '/api/v1/export/expenses?from=' . $today . '&to=' . $today,
            '/api/v1/export/maintenance?vehicle_uuid=' . $vehicle->uuid . '&status=active',
            '/api/v1/export/users?name=Export&email=driver',
        ];
        foreach ($filtered as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertGreaterThan(1, substr_count($response->streamedContent(), "\n"));
        }
    }

    public function test_export_rejects_an_invalid_format(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/export/vehicles?format=pdf')
            ->assertUnprocessable();
    }
}
