<?php

namespace App\Modules\Sync\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Sync\Requests\SyncRequest;
use App\Modules\Sync\Services\SyncService;
use Illuminate\Http\JsonResponse;

class SyncController
{
    use ApiResponseTrait;

    public function __construct(private SyncService $service) {}

    public function sync(SyncRequest $request): JsonResponse
    {
        $result = $this->service->process(
            $request->validated(),
            auth()->user()
        );

        return $this->successResponse($result, 200, 'Synchronisation terminée.');
    }
}
