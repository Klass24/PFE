<?php

use App\Modules\Expense\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('expenses',                             [ExpenseController::class, 'listAll']);
    Route::get('missions/{missionUuid}/expenses',      [ExpenseController::class, 'index']);
    Route::post('missions/{missionUuid}/expenses',     [ExpenseController::class, 'store']);
    Route::get('expenses/{uuid}',                      [ExpenseController::class, 'show']);
    Route::put('expenses/{uuid}',                      [ExpenseController::class, 'update']);
    Route::delete('expenses/{uuid}',                  [ExpenseController::class, 'destroy']);
    Route::post('expenses/{uuid}/receipt',             [ExpenseController::class, 'uploadReceipt']);
});
