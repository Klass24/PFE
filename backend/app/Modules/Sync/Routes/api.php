<?php

use App\Modules\Sync\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('sync', [SyncController::class, 'sync']);
});
