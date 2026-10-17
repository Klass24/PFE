<?php

use App\Modules\Driver\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('drivers', DriverController::class)->parameters(['drivers' => 'uuid']);
});
