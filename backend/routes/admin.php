<?php

use App\Infrastructure\Http\Controllers\Admin\AccountController;
use App\Infrastructure\Http\Controllers\Admin\TenantController;
use App\Infrastructure\Http\Controllers\Admin\SystemRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin.only'])->prefix('admin')->group(function () {
    
    // Account management
    Route::apiResource('accounts', AccountController::class);
    
    // Tenant management
    Route::apiResource('tenants', TenantController::class);
    
    // System roles
    Route::get('/system-roles', [SystemRoleController::class, 'index']);
    
    // Tenant user management
    Route::prefix('tenants/{tenant}')->group(function () {
        Route::get('/users', [TenantController::class, 'users']);
        Route::post('/users', [TenantController::class, 'assignUser']);
        Route::delete('/users/{profile}', [TenantController::class, 'removeUser']);
        Route::put('/users/{profile}/role', [TenantController::class, 'updateUserRole']);
    });
});