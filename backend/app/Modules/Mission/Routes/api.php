<?php

use App\Modules\Mission\Controllers\MissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('missions', MissionController::class)->parameters(['missions' => 'uuid']);
    Route::post('missions/{uuid}/start',    [MissionController::class, 'start']);
    Route::post('missions/{uuid}/complete', [MissionController::class, 'complete']);
    Route::post('missions/{uuid}/cancel',   [MissionController::class, 'cancel']);
});
