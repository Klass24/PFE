<?php

namespace App\Modules\Tracking\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Tracking\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController
{
    use ApiResponseTrait;

    public function __construct(private TrackingService $service) {}

    public function recordPoint(Request $request): JsonResponse
    {
        $request->validate([
            'mission_uuid' => 'required|exists:missions,uuid',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'altitude'     => 'nullable|numeric',
            'accuracy'     => 'nullable|numeric|min:0',
            'speed'        => 'nullable|numeric|min:0',
            'heading'      => 'nullable|numeric|between:0,360',
            'recorded_at'  => 'nullable|date',
        ]);

        $point = $this->service->recordPoint($request->all());
        return $this->successResponse($point, 201, 'Position enregistrée.');
    }

    public function recordBatch(Request $request): JsonResponse
    {
        $request->validate([
            'points'              => 'required|array|min:1|max:500',
            'points.*.mission_uuid' => 'required|exists:missions,uuid',
            'points.*.latitude'   => 'required|numeric|between:-90,90',
            'points.*.longitude'  => 'required|numeric|between:-180,180',
            'points.*.speed'      => 'nullable|numeric|min:0',
            'points.*.recorded_at' => 'nullable|date',
        ]);

        $count = $this->service->recordBatch($request->points);
        return $this->successResponse(['count' => $count], 201, "{$count} positions enregistrées.");
    }

    public function missionLive(string $missionUuid): JsonResponse
    {
        $data = $this->service->getMissionLive($missionUuid);
        return $this->successResponse($data);
    }

    public function missionHistory(Request $request, string $missionUuid): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $data = $this->service->getMissionHistory($missionUuid, $request->from, $request->to);
        return $this->successResponse($data);
    }

    public function activeTracking(): JsonResponse
    {
        return $this->successResponse($this->service->getActiveTracking());
    }

    // Geofences
    public function storeGeofence(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:circle,polygon',
            'center_lat'     => 'required_if:type,circle|numeric|between:-90,90',
            'center_lng'     => 'required_if:type,circle|numeric|between:-180,180',
            'radius_meters'  => 'required_if:type,circle|integer|min:50|max:100000',
            'polygon_points' => 'required_if:type,polygon|array',
            'trigger_on'     => 'nullable|in:enter,exit,both',
        ]);

        $geofence = $this->service->createGeofence($request->all());
        return $this->successResponse($geofence, 201, 'Geofence créé.');
    }

    public function geofences(): JsonResponse
    {
        return $this->successResponse($this->service->getGeofences());
    }
}
