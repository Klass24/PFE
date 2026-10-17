<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Document\Models\Document;
use App\Modules\Driver\Models\Driver;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Mission\Models\Mission;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function getStats(int $companyId): array
    {
        return Cache::remember("dashboard:stats:{$companyId}", self::CACHE_TTL, function () {
            return [
                'total_vehicles'       => Vehicle::count(),
                'available_vehicles'   => Vehicle::where('status', 'available')->count(),
                'on_mission_vehicles'  => Vehicle::where('status', 'on_mission')->count(),
                'maintenance_vehicles' => Vehicle::where('status', 'maintenance')->count(),

                'total_drivers'        => Driver::where('status', '!=', 'inactive')->count(),
                'active_missions'      => Mission::where('status', 'in_progress')->count(),
                'planned_missions'     => Mission::where('status', 'planned')->count(),

                'total_expenses_month' => (float) Expense::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),

                'expiring_documents_30' => Document::whereNotNull('expiry_date')
                    ->where('expiry_date', '>', now())
                    ->where('expiry_date', '<=', now()->addDays(30))
                    ->count(),

                'expired_documents' => Document::whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now())
                    ->count(),

                'incidents_this_month' => Incident::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];
        });
    }

    public function getExpensesByVehicle(int $companyId, string $from, string $to): mixed
    {
        $cacheKey = "dashboard:expenses_by_vehicle:{$companyId}:{$from}:{$to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $companyId) {
            return DB::table('expenses')
                ->join('missions', 'expenses.mission_id', '=', 'missions.id')
                ->join('vehicles', 'missions.vehicle_id', '=', 'vehicles.id')
                ->where('expenses.company_id', $companyId)
                ->whereBetween('expenses.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->selectRaw('vehicles.uuid, vehicles.plate_number, vehicles.brand, vehicles.model, SUM(expenses.amount) as total')
                ->groupBy('vehicles.id', 'vehicles.uuid', 'vehicles.plate_number', 'vehicles.brand', 'vehicles.model')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        });
    }

    public function getIncidentsByType(int $companyId, string $from, string $to): mixed
    {
        $cacheKey = "dashboard:incidents_by_type:{$companyId}:{$from}:{$to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $companyId) {
            return DB::table('incidents')
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderByDesc('count')
                ->get();
        });
    }

    public function getCostPerMission(int $companyId, string $from, string $to): mixed
    {
        $cacheKey = "dashboard:cost_per_mission:{$companyId}:{$from}:{$to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $companyId) {
            return DB::table('missions')
                ->where('missions.company_id', $companyId)
                ->where('missions.status', 'completed')
                ->whereBetween('missions.start_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->selectRaw('missions.uuid, missions.origin, missions.destination, missions.total_cost, missions.start_date')
                ->orderByDesc('missions.total_cost')
                ->limit(10)
                ->get();
        });
    }

    public function getExpiringDocuments(int $companyId): mixed
    {
        $cacheKey = "dashboard:expiring_docs:{$companyId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Document::whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->orderBy('expiry_date')
                ->with('documentable')
                ->get()
                ->map(fn ($doc) => [
                    'uuid'        => $doc->uuid,
                    'type'        => $doc->type,
                    'expiry_date' => $doc->expiry_date?->format('d/m/Y'),
                    'days_left'   => max(0, now()->diffInDays($doc->expiry_date, false)),
                    'entity'      => class_basename($doc->documentable_type),
                    'entity_uuid' => $doc->documentable?->uuid,
                    'status'      => $doc->expiry_date < now() ? 'expired' : ($doc->expiry_date <= now()->addDays(7) ? 'critical' : 'warning'),
                ]);
        });
    }

    public function getDriverLeaderboard(int $companyId): array
    {
        $cacheKey = "dashboard:driver_leaderboard:{$companyId}";

        return Cache::remember($cacheKey, 86400, function () use ($companyId) {
            // Efficiency: cost per mission
            $efficiency = DB::table('missions')
                ->join('drivers', 'missions.driver_id', '=', 'drivers.id')
                ->join('users', 'drivers.user_id', '=', 'users.id')
                ->where('missions.company_id', $companyId)
                ->where('missions.status', 'completed')
                ->selectRaw('drivers.uuid, users.name, COUNT(missions.id) as missions_count, COALESCE(AVG(missions.total_cost), 0) as avg_cost')
                ->groupBy('drivers.id', 'drivers.uuid', 'users.name')
                ->orderBy('avg_cost')
                ->limit(10)
                ->get();

            // Safety: fewest incidents
            $safety = DB::table('drivers')
                ->join('users', 'drivers.user_id', '=', 'users.id')
                ->leftJoin('missions', 'missions.driver_id', '=', 'drivers.id')
                ->leftJoin('incidents', 'incidents.mission_id', '=', 'missions.id')
                ->where('drivers.company_id', $companyId)
                ->selectRaw('drivers.uuid, users.name, COUNT(DISTINCT missions.id) as missions_count, COUNT(incidents.id) as incidents_count')
                ->groupBy('drivers.id', 'drivers.uuid', 'users.name')
                ->havingRaw('COUNT(DISTINCT missions.id) > 0')
                ->orderBy('incidents_count')
                ->limit(10)
                ->get();

            return [
                'efficiency' => $efficiency,
                'safety'     => $safety,
            ];
        });
    }
}
