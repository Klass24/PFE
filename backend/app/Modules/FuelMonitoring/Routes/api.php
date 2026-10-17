<?php

use App\Modules\FuelMonitoring\Controllers\FuelMonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/fuel')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',              [FuelMonitoringController::class, 'index']);
    Route::post('/',             [FuelMonitoringController::class, 'store']);
    Route::get('anomalies',      [FuelMonitoringController::class, 'anomalies']);
    Route::put('anomalies/{uuid}', [FuelMonitoringController::class, 'resolveAnomaly']);
    Route::get('analytics',      [FuelMonitoringController::class, 'analytics']);
});
