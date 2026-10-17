<?php

use App\Modules\Audit\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index']);
});
