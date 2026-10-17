<?php

namespace App\Modules\Export\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Export\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController
{
    use ApiResponseTrait;

    public function __construct(private ImportService $service) {}

    public function vehicles(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importVehicles(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} véhicule(s) importé(s)");
    }

    public function drivers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importDrivers(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} chauffeur(s) importé(s)");
    }

    public function missions(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importMissions(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} mission(s) importée(s)");
    }

    public function expenses(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importExpenses(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} dépense(s) importée(s)");
    }

    public function incidents(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importIncidents(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} incident(s) importé(s)");
    }

    public function documents(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importDocuments(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} document(s) importé(s)");
    }

    public function maintenance(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importMaintenance(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} maintenance(s) importée(s)");
    }

    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importUsers(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        return $this->successResponse($result, 200, "{$result['imported']} utilisateur(s) importé(s)");
    }
}
