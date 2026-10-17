<?php

use App\Modules\AlertRules\Controllers\AlertRuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/alert-rules')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',         [AlertRuleController::class, 'index']);
    Route::post('/',        [AlertRuleController::class, 'store']);
    Route::put('{uuid}',    [AlertRuleController::class, 'update']);
    Route::delete('{uuid}', [AlertRuleController::class, 'destroy']);
});
