<?php

use App\Modules\Checklist\Controllers\ChecklistController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Standalone checklist (for departure checklist page)
    Route::get('checklist/current', [ChecklistController::class, 'current']);
    Route::get('checklist', [ChecklistController::class, 'index']);
    Route::post('checklist/validate', [ChecklistController::class, 'validateStandalone']);
    
    // Check-list imbriquée sous mission
    Route::get ('missions/{missionUuid}/checklist',                         [ChecklistController::class, 'show']);
    Route::put ('missions/{missionUuid}/checklist/items',                   [ChecklistController::class, 'updateItems']);
    Route::post('missions/{missionUuid}/checklist/items/{itemId}/photo',    [ChecklistController::class, 'uploadItemPhoto']);
    Route::post('missions/{missionUuid}/checklist/submit',                  [ChecklistController::class, 'submit']);
    Route::post('missions/{missionUuid}/checklist/validate',                [ChecklistController::class, 'validate']);
});
