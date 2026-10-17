<?php

use App\Modules\Incident\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('incidents',                         [IncidentController::class, 'listAll']);
    Route::get('missions/{missionUuid}/incidents',  [IncidentController::class, 'index']);
    Route::post('missions/{missionUuid}/incidents', [IncidentController::class, 'store']);
    Route::get('incidents/{uuid}',                  [IncidentController::class, 'show']);
    Route::delete('incidents/{uuid}',               [IncidentController::class, 'destroy']);
});
