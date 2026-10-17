<?php

use App\Modules\ApiToken\Controllers\ApiTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('api-tokens', [ApiTokenController::class, 'index']);
    Route::post('api-tokens', [ApiTokenController::class, 'store']);
    Route::delete('api-tokens/{uuid}', [ApiTokenController::class, 'destroy']);
});
