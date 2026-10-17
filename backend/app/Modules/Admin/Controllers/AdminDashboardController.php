<?php

namespace App\Modules\Admin\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Admin\Services\AdminService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController
{
    use ApiResponseTrait;

    public function __construct(private AdminService $service) {}

    public function stats(): JsonResponse
    {
        return $this->successResponse($this->service->getStats());
    }
}
