<?php

namespace Tests\Feature;

use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase
{
    private function importCsv(string $resource, string $csv): array
    {
        $response = $this->postJson('/api/v1/import/' . $resource, [
            'file' => UploadedFile::fake()->createWithContent('import.csv', $csv),
        ]);
        $response->assertOk();

        return $response->json('data');
    }

    public function test_imports_related_records_from_csv_and_reports_invalid_rows(): void
    {
        $this->actingAsAdmin();
        $result = $this->importCsv('vehicles', "\xEF\xBB\xBF" . "Plaque;Marque;Modèle;Année\nIMPORT-001;Toyota;Hilux;2024\n");
        $this->assertSame(['imported' => 1, 'errors' => []], $result);
        $vehicle = Vehicle::where('plate_number', 'IMPORT-001')->firstOrFail();

        $result = $this->importCsv('drivers', "name;email;license_number;license_expiry\nImport Driver;import-driver@example.test;IMPORT-LIC;2030-01-01\n");
        $this->assertSame(['imported' => 1, 'errors' => []], $result);
        $driver = Driver::where('license_number', 'IMPORT-LIC')->firstOrFail();

        $result = $this->importCsv('missions', "origin;destination;planned_departure;vehicle_uuid;driver_uuid\nA;B;2026-01-01;{$vehicle->uuid};{$driver->uuid}\nA;B;2026-01-01;invalid;{$driver->uuid}\n");
        $this->assertSame(1, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringStartsWith('Ligne 3:', $result['errors'][0]);
        $mission = Mission::where('vehicle_id', $vehicle->id)->firstOrFail();

        $result = $this->importCsv('expenses', "mission_uuid;type;amount;liters;mileage;notes\n{$mission->uuid};fuel;250;20;1500;test\nunknown;toll;10;;;\n");
        $this->assertSame(1, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertDatabaseHas('expenses', ['mission_id' => $mission->id, 'amount' => 250]);

        $result = $this->importCsv('incidents', "mission_uuid;type;description;latitude;longitude\n{$mission->uuid};breakdown;Panne;1.5;2.5\n");
        $this->assertSame(['imported' => 1, 'errors' => []], $result);
        $this->assertDatabaseHas('incidents', ['mission_id' => $mission->id, 'description' => 'Panne']);

        $result = $this->importCsv('documents', "documentable_type;documentable_uuid;type;file_path;issue_date;expiry_date;alert_sent\nvehicle;{$vehicle->uuid};insurance;insurance.pdf;2025-01-01;2030-01-01;true\n");
        $this->assertSame(['imported' => 1, 'errors' => []], $result);
        $this->assertDatabaseHas('documents', ['documentable_id' => $vehicle->id, 'file_path' => 'insurance.pdf']);

        $result = $this->importCsv('maintenance', "vehicle_uuid;maintenance_type;frequency_km;frequency_months;last_service_date;last_service_km;next_due_date;next_due_km;status;notes\n{$vehicle->uuid};oil_change;5000;6;2025-01-01;1000;2025-07-01;6000;active;test\n");
        $this->assertSame(['imported' => 1, 'errors' => []], $result);
        $this->assertDatabaseHas('maintenance_schedules', ['vehicle_id' => $vehicle->id, 'next_due_km' => 6000]);

        $result = $this->importCsv('users', "name;email;role;is_active\nImport Manager;manager-import@example.test;manager;true\nInvalid;invalid-import@example.test;invalid;true\n");
        $this->assertSame(1, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('role invalide', $result['errors'][0]);
        $this->assertDatabaseHas('users', ['email' => 'manager-import@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'invalid-import@example.test']);
    }

    public function test_import_reports_missing_columns(): void
    {
        $this->actingAsAdmin()->postJson('/api/v1/import/vehicles', [
            'file' => UploadedFile::fake()->createWithContent('import.csv', "plaque;marque\nABC;Toyota\n"),
        ])->assertUnprocessable();
        $this->assertDatabaseMissing('vehicles', ['plate_number' => 'ABC']);
    }
}
