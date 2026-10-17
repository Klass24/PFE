<?php

namespace App\Modules\FuelMonitoring\Services;

use App\Core\Services\BaseService;
use App\Modules\FuelMonitoring\Models\FuelAnomaly;
use App\Modules\FuelMonitoring\Models\FuelEntry;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuelMonitoringService extends BaseService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return FuelEntry::with('vehicle')
            ->when($filters['vehicle_uuid'] ?? null, function ($q, $uuid) {
                $q->whereHas('vehicle', fn($v) => $v->where('uuid', $uuid));
            })
            ->when($filters['from'] ?? null, fn($q, $d) => $q->where('recorded_at', '>=', $d))
            ->when($filters['to'] ?? null, fn($q, $d) => $q->where('recorded_at', '<=', $d))
            ->orderBy('recorded_at', 'desc')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function recordConsumption(array $data): FuelEntry
    {
        $vehicle = Vehicle::where('uuid', $data['vehicle_uuid'])->firstOrFail();

        $entry = FuelEntry::create([
            'vehicle_id'      => $vehicle->id,
            'company_id'      => auth()->user()->company_id,
            'mission_id'      => $data['mission_id'] ?? null,
            'liters'          => $data['liters'],
            'cost_per_liter'  => $data['cost_per_liter'],
            'total_cost'      => $data['liters'] * $data['cost_per_liter'],
            'odometer_reading' => $data['odometer_reading'],
            'station_name'    => $data['station_name'] ?? null,
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
            'recorded_at'     => $data['recorded_at'] ?? now(),
        ]);

        // Update vehicle mileage
        if ($data['odometer_reading'] > $vehicle->mileage) {
            $vehicle->update(['mileage' => $data['odometer_reading']]);
        }

        // Run anomaly detection async
        $this->detectAnomaly($entry, $vehicle);

        Log::info('Fuel entry recorded', ['entry_id' => $entry->id, 'vehicle' => $vehicle->id]);
        return $entry;
    }

    public function detectAnomaly(FuelEntry $entry, Vehicle $vehicle): ?FuelAnomaly
    {
        // Calculate baseline: average L/100km for this vehicle over last 3 months
        $baseline = FuelEntry::where('vehicle_id', $vehicle->id)
            ->where('recorded_at', '>=', now()->subMonths(3))
            ->where('id', '!=', $entry->id)
            ->selectRaw('AVG(liters / NULLIF(odometer_reading - LAG(odometer_reading) OVER (ORDER BY recorded_at), 0) * 100) as avg_consumption')
            ->value('avg_consumption');

        if (!$baseline || $baseline <= 0) return null;

        // Calculate previous entry to get distance
        $previousEntry = FuelEntry::where('vehicle_id', $vehicle->id)
            ->where('id', '<', $entry->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousEntry) return null;

        $distance = $entry->odometer_reading - $previousEntry->odometer_reading;
        if ($distance <= 0) return null;

        $actualConsumption = ($entry->liters / $distance) * 100;
        $deviationPercent = (($actualConsumption - $baseline) / $baseline) * 100;

        // Alert if deviation > 25%
        if ($deviationPercent > 25) {
            $anomalyType = $deviationPercent > 50 ? 'potential_theft' : 'excessive_consumption';

            $anomaly = FuelAnomaly::create([
                'vehicle_id'           => $vehicle->id,
                'company_id'           => auth()->user()->company_id,
                'mission_id'           => $entry->mission_id,
                'anomaly_type'         => $anomalyType,
                'expected_consumption' => $baseline,
                'actual_consumption'   => $actualConsumption,
                'deviation_percent'    => $deviationPercent,
            ]);

            Log::warning('Fuel anomaly detected', [
                'anomaly_id' => $anomaly->id,
                'vehicle'    => $vehicle->id,
                'deviation'  => round($deviationPercent, 1) . '%',
            ]);

            return $anomaly;
        }

        return null;
    }

    public function getAnomalies(array $filters = []): LengthAwarePaginator
    {
        return FuelAnomaly::with('vehicle')
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function resolveAnomaly(FuelAnomaly $anomaly, string $status, ?string $notes = null): FuelAnomaly
    {
        $anomaly->update([
            'status' => $status,
            'notes'  => $notes,
        ]);
        return $anomaly;
    }

    public function getAnalytics(int $companyId, string $from, string $to): array
    {
        $consumptionByVehicle = DB::table('fuel_entries')
            ->join('vehicles', 'fuel_entries.vehicle_id', '=', 'vehicles.id')
            ->where('fuel_entries.company_id', $companyId)
            ->whereBetween('fuel_entries.recorded_at', [$from, $to])
            ->selectRaw('vehicles.uuid, vehicles.plate_number, SUM(fuel_entries.liters) as total_liters, SUM(fuel_entries.total_cost) as total_cost, COUNT(*) as entries_count')
            ->groupBy('vehicles.id', 'vehicles.uuid', 'vehicles.plate_number')
            ->orderByDesc('total_cost')
            ->get();

        $totalCost = DB::table('fuel_entries')
            ->where('company_id', $companyId)
            ->whereBetween('recorded_at', [$from, $to])
            ->sum('total_cost');

        $totalLiters = DB::table('fuel_entries')
            ->where('company_id', $companyId)
            ->whereBetween('recorded_at', [$from, $to])
            ->sum('liters');

        return [
            'by_vehicle'   => $consumptionByVehicle,
            'total_cost'   => (float) $totalCost,
            'total_liters' => (float) $totalLiters,
        ];
    }
}
