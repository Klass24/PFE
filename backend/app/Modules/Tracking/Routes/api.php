<?php

use App\Modules\Tracking\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/tracking')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('record-point',      [TrackingController::class, 'recordPoint']);
    Route::post('record-batch',      [TrackingController::class, 'recordBatch']);
    Route::get('active',             [TrackingController::class, 'activeTracking']);
    Route::get('mission/{missionUuid}/live',    [TrackingController::class, 'missionLive']);
    Route::get('mission/{missionUuid}/history', [TrackingController::class, 'missionHistory']);

    // Geofences
    Route::get('geofences',          [TrackingController::class, 'geofences']);
    Route::post('geofences',         [TrackingController::class, 'storeGeofence']);
});
