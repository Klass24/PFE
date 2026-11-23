<?php

namespace Tests\Feature;

use App\Modules\FuelMonitoring\Models\FuelAnomaly;
use App\Modules\FuelMonitoring\Models\FuelEntry;
use App\Modules\Vehicle\Models\Vehicle;
use Tests\TestCase;

class FuelMonitoringTest extends TestCase
{
    public function test_fuel_entries_analytics_and_anomaly_resolution_are_scoped_to_company(): void
    {
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'FUEL-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
        ]);
        $entry = FuelEntry::create([
            'vehicle_id' => $vehicle->id,
            'company_id' => $this->company->id,
            'liters' => 40,
            'cost_per_liter' => 2,
            'total_cost' => 80,
            'odometer_reading' => 10000,
            'recorded_at' => '2025-02-01 10:00:00',
        ]);
        $anomaly = FuelAnomaly::create([
            'vehicle_id' => $vehicle->id,
            'company_id' => $this->company->id,
            'anomaly_type' => 'excessive_consumption',
            'expected_consumption' => 10,
            'actual_consumption' => 14,
            'deviation_percent' => 40,
        ]);

        $this->getJson('/api/v1/fuel?vehicle_uuid=' . $vehicle->uuid . '&from=2025-01-01&to=2025-03-01')
            ->assertOk()->assertJsonFragment(['uuid' => (string) $entry->uuid]);
        $this->getJson('/api/v1/fuel/analytics?from=2025-01-01&to=2025-03-01')
            ->assertOk()->assertJsonPath('data.total_liters', 40)->assertJsonPath('data.total_cost', 80);
        $this->getJson('/api/v1/fuel/anomalies')->assertOk()->assertJsonFragment(['uuid' => (string) $anomaly->uuid]);
        $this->putJson('/api/v1/fuel/anomalies/' . $anomaly->uuid, ['status' => 'confirmed', 'notes' => 'Reviewed'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertSame('Reviewed', $anomaly->fresh()->notes);
        $this->postJson('/api/v1/fuel', [])->assertUnprocessable();
        $this->putJson('/api/v1/fuel/anomalies/' . $anomaly->uuid, ['status' => 'unknown'])->assertUnprocessable();
    }
}
