<?php

use App\Modules\Maintenance\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/maintenance')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',             [MaintenanceController::class, 'index']);
    Route::post('/',            [MaintenanceController::class, 'store']);
    Route::get('alerts',        [MaintenanceController::class, 'alerts']);
    Route::get('{uuid}',        [MaintenanceController::class, 'show']);
    Route::post('{uuid}/complete', [MaintenanceController::class, 'complete']);
    Route::delete('{uuid}',     [MaintenanceController::class, 'destroy']);
});
