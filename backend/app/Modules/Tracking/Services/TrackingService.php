<?php

namespace App\Modules\Tracking\Services;

use App\Core\Services\BaseService;
use App\Modules\Mission\Models\Mission;
use App\Modules\Tracking\Models\Geofence;
use App\Modules\Tracking\Models\TrackingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackingService extends BaseService
{
    public function recordPoint(array $data): TrackingPoint
    {
        $mission = Mission::where('uuid', $data['mission_uuid'])->firstOrFail();

        $point = TrackingPoint::create([
            'mission_id'  => $mission->id,
            'company_id'  => auth()->user()->company_id,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'altitude'    => $data['altitude'] ?? null,
            'accuracy'    => $data['accuracy'] ?? null,
            'speed'       => $data['speed'] ?? null,
            'heading'     => $data['heading'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'synced_at'   => now(),
        ]);

        // Check geofences
        $this->checkGeofences($point);

        return $point;
    }

    public function recordBatch(array $points): int
    {
        $count = 0;
        DB::transaction(function () use ($points, &$count) {
            foreach ($points as $pointData) {
                $this->recordPoint($pointData);
                $count++;
            }
        });
        Log::info('Tracking batch recorded', ['count' => $count]);
        return $count;
    }

    public function getMissionLive(string $missionUuid): array
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();

        $latest = TrackingPoint::where('mission_id', $mission->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $path = TrackingPoint::where('mission_id', $mission->id)
            ->orderBy('recorded_at')
            ->select('latitude', 'longitude', 'speed', 'recorded_at')
            ->get();

        return [
            'mission_uuid' => $missionUuid,
            'latest'       => $latest,
            'path'         => $path,
            'total_points' => $path->count(),
        ];
    }

    public function getMissionHistory(string $missionUuid, ?string $from = null, ?string $to = null): array
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();

        $query = TrackingPoint::where('mission_id', $mission->id)
            ->orderBy('recorded_at');

        if ($from) $query->where('recorded_at', '>=', $from);
        if ($to) $query->where('recorded_at', '<=', $to);

        $points = $query->get();

        // Calculate stats
        $totalDistance = 0;
        $maxSpeed      = 0;
        $previous      = null;

        foreach ($points as $point) {
            if ($point->speed && $point->speed > $maxSpeed) {
                $maxSpeed = $point->speed;
            }
            if ($previous) {
                $totalDistance += $this->haversineDistance(
                    $previous->latitude, $previous->longitude,
                    $point->latitude, $point->longitude
                );
            }
            $previous = $point;
        }

        return [
            'points'         => $points,
            'total_points'   => $points->count(),
            'total_distance' => round($totalDistance / 1000, 2), // km
            'max_speed'      => $maxSpeed,
            'avg_speed'      => $points->avg('speed'),
        ];
    }

    public function getActiveTracking(): array
    {
        $companyId = auth()->user()->company_id;

        return DB::table('tracking_points')
            ->join('missions', 'tracking_points.mission_id', '=', 'missions.id')
            ->join('vehicles', 'missions.vehicle_id', '=', 'vehicles.id')
            ->join('drivers', 'missions.driver_id', '=', 'drivers.id')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->where('tracking_points.company_id', $companyId)
            ->where('missions.status', 'in_progress')
            ->whereRaw('tracking_points.id = (SELECT MAX(tp2.id) FROM tracking_points tp2 WHERE tp2.mission_id = tracking_points.mission_id)')
            ->select([
                'missions.uuid as mission_uuid',
                'vehicles.plate_number',
                'users.name as driver_name',
                'tracking_points.latitude',
                'tracking_points.longitude',
                'tracking_points.speed',
                'tracking_points.recorded_at',
            ])
            ->get()
            ->toArray();
    }

    // Geofencing
    public function createGeofence(array $data): Geofence
    {
        return Geofence::create([
            'company_id'     => auth()->user()->company_id,
            'name'           => $data['name'],
            'type'           => $data['type'] ?? 'circle',
            'center_lat'     => $data['center_lat'] ?? null,
            'center_lng'     => $data['center_lng'] ?? null,
            'radius_meters'  => $data['radius_meters'] ?? null,
            'polygon_points' => $data['polygon_points'] ?? null,
            'trigger_on'     => $data['trigger_on'] ?? 'both',
        ]);
    }

    public function getGeofences(): \Illuminate\Database\Eloquent\Collection
    {
        return Geofence::where('is_active', true)->get();
    }

    private function checkGeofences(TrackingPoint $point): void
    {
        $geofences = Geofence::where('is_active', true)->get();

        foreach ($geofences as $geofence) {
            $inside = $geofence->containsPoint($point->latitude, $point->longitude);
            // TODO: Compare with previous point to detect enter/exit and trigger notifications
        }
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
