<?php

use App\Modules\Permission\Controllers\PermissionController;
use App\Modules\Permission\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {

    Route::get('permissions', [PermissionController::class, 'index']);

    // Gestion par rôle (super_admin uniquement)
    Route::get('roles/{role}/permissions',  [PermissionController::class, 'rolePermissions']);
    Route::put('roles/{role}/permissions',  [PermissionController::class, 'updateRolePermissions']);

    // Gestion par utilisateur (admin → drivers de son entreprise, super_admin → tous)
    Route::get('users/{uuid}/permissions',  [PermissionController::class, 'userPermissions']);
    Route::put('users/{uuid}/permissions',  [PermissionController::class, 'updateUserPermissions']);

    // CRUD Rôles (super_admin uniquement)
    Route::apiResource('roles', RoleController::class);
});
