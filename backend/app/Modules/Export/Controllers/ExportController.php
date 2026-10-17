<?php

namespace App\Modules\Export\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Export\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController
{
    use ApiResponseTrait;

    public function __construct(private ExportService $service) {}

    public function vehicles(Request $request): StreamedResponse
    {
        $request->validate([
            'status' => 'nullable|string',
            'plate_number' => 'nullable|string',
            'model'  => 'nullable|string',
            'type'   => 'nullable|string',
            'fuel_type' => 'nullable|string',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportVehicles(
            $request->only(['status', 'plate_number', 'brand', 'model', 'type', 'fuel_type', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function drivers(Request $request): StreamedResponse
    {
        $request->validate([
            'status' => 'nullable|string',
            'name'   => 'nullable|string',
            'email'  => 'nullable|string',
            'license_number' => 'nullable|string',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportDrivers(
            $request->only(['status', 'name', 'email', 'license_number', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function missions(Request $request): StreamedResponse
    {
        $request->validate([
            'status' => 'nullable|in:planned,in_progress,completed,cancelled',
            'origin' => 'nullable|string',
            'destination' => 'nullable|string',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportMissions(
            $request->only(['status', 'origin', 'destination', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function expenses(Request $request): StreamedResponse
    {
        $request->validate([
            'type'   => 'nullable|in:fuel,toll,repair,parking,weighing,other',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportExpenses(
            $request->only(['type', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function incidents(Request $request): StreamedResponse
    {
        $request->validate([
            'type'   => 'nullable|in:accident,breakdown,flat_tire,traffic,police_check,mechanical_issue,other',
            'sync_status' => 'nullable|in:synced,pending',
            'description' => 'nullable|string',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportIncidents(
            $request->only(['type', 'sync_status', 'description', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function documents(Request $request): StreamedResponse
    {
        $request->validate([
            'type'   => 'nullable|string',
            'documentable_type' => 'nullable|string',
            'status' => 'nullable|in:expired,expiring,valid',
            'alert_sent' => 'nullable|boolean',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportDocuments(
            $request->only(['type', 'documentable_type', 'status', 'alert_sent', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function maintenance(Request $request): StreamedResponse
    {
        $request->validate([
            'maintenance_type' => 'nullable|string',
            'status' => 'nullable|string',
            'vehicle_uuid' => 'nullable|string',
            'vehicle_search' => 'nullable|string',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportMaintenance(
            $request->only(['maintenance_type', 'status', 'vehicle_uuid', 'vehicle_search', 'from', 'to']),
            $request->get('format', 'csv')
        );
    }

    public function users(Request $request): StreamedResponse
    {
        $request->validate([
            'role'   => 'nullable|in:admin,manager,driver',
            'status' => 'nullable|in:active,inactive',
            'name'   => 'nullable|string',
            'email'  => 'nullable|string',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        return $this->service->exportUsers(
            $request->only(['role', 'status', 'name', 'email']),
            $request->get('format', 'csv')
        );
    }
}
