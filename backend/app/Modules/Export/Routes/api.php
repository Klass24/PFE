<?php

use App\Modules\Export\Controllers\ExportController;
use App\Modules\Export\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/export')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('vehicles',  [ExportController::class, 'vehicles']);
    Route::get('drivers',   [ExportController::class, 'drivers']);
    Route::get('missions',  [ExportController::class, 'missions']);
    Route::get('expenses',  [ExportController::class, 'expenses']);
    Route::get('incidents', [ExportController::class, 'incidents']);
    Route::get('documents', [ExportController::class, 'documents']);
    Route::get('maintenance', [ExportController::class, 'maintenance']);
    Route::get('users', [ExportController::class, 'users']);
});

Route::prefix('api/v1/import')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('vehicles',  [ImportController::class, 'vehicles']);
    Route::post('drivers',   [ImportController::class, 'drivers']);
    Route::post('missions',  [ImportController::class, 'missions']);
    Route::post('expenses',  [ImportController::class, 'expenses']);
    Route::post('incidents', [ImportController::class, 'incidents']);
    Route::post('documents', [ImportController::class, 'documents']);
    Route::post('maintenance', [ImportController::class, 'maintenance']);
    Route::post('users', [ImportController::class, 'users']);
});
