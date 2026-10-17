<?php

use App\Modules\Vehicle\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // ── Catalog (doit précéder apiResource pour éviter conflit {uuid}) ─────────
    Route::get('vehicles/catalog/brands',          [VehicleController::class, 'catalogBrands']);
    Route::get('vehicles/catalog/models/{brand}',  [VehicleController::class, 'catalogModels']);

    Route::apiResource('vehicles', VehicleController::class)->parameters(['vehicles' => 'uuid']);
    Route::post('vehicles/{uuid}/image', [VehicleController::class, 'uploadImage']);
});
