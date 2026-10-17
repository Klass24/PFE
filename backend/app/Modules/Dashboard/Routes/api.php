<?php

use App\Modules\Dashboard\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/dashboard')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('stats',               [DashboardController::class, 'stats']);
    Route::get('expenses-by-vehicle', [DashboardController::class, 'expensesByVehicle']);
    Route::get('incidents-by-type',   [DashboardController::class, 'incidentsByType']);
    Route::get('cost-per-mission',    [DashboardController::class, 'costPerMission']);
    Route::get('expiring-documents',  [DashboardController::class, 'expiringDocuments']);
    Route::get('driver-leaderboard',  [DashboardController::class, 'driverLeaderboard']);
});
