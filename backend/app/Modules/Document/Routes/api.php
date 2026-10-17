<?php

use App\Modules\Document\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('documents',          [DocumentController::class, 'index']);
    Route::post('documents',         [DocumentController::class, 'store']);
    Route::get('documents/expiring', [DocumentController::class, 'expiring']);
    Route::get('documents/{uuid}',   [DocumentController::class, 'show']);
    Route::delete('documents/{uuid}',[DocumentController::class, 'destroy']);
});
