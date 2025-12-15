<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Auth\LoginController;
use App\Infrastructure\Http\Controllers\Auth\RegisterController;
use App\Infrastructure\Http\Controllers\Auth\QuickLoginController;
use App\Infrastructure\Http\Controllers\Tenant\UserManagementController;
use App\Infrastructure\Http\Controllers\Tenant\DocumentController;
use App\Infrastructure\Http\Controllers\Tenant\CategoryGroupController;
use App\Infrastructure\Http\Controllers\Tenant\CategoryController;
use App\Infrastructure\Http\Controllers\Documents\SignedDocumentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::prefix('auth')->group(function () {
    // Registration
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/check-username', [RegisterController::class, 'checkUsername']);
    Route::post('/check-email', [RegisterController::class, 'checkEmail']);
    
    // Login/Logout
    Route::post('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [LoginController::class, 'me'])->middleware('auth:sanctum');
    
    // Quick login for development
    Route::get('/quick-login', [QuickLoginController::class, 'index']);
    Route::post('/quick-login', [QuickLoginController::class, 'login']);
});

// Tenant routes (require auth + tenant context)
Route::middleware(['auth:sanctum'])->prefix('tenant')->group(function () {
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::get('/{profileId}', [UserManagementController::class, 'show']);
        Route::patch('/{profileId}/role', [UserManagementController::class, 'updateRole']);
        Route::patch('/{profileId}/deactivate', [UserManagementController::class, 'deactivate']);
        Route::patch('/{profileId}/activate', [UserManagementController::class, 'activate']);
        Route::delete('/{profileId}', [UserManagementController::class, 'destroy']);
        Route::post('/{profileId}/resend-invitation', [UserManagementController::class, 'resendInvitation']);
    });
    
    // Document Management
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/statistics', [DocumentController::class, 'statistics']);
        Route::post('/bulk-categorize', [DocumentController::class, 'bulkCategorize']);
        Route::post('/bulk-ai-categorize', [DocumentController::class, 'bulkAiCategorize']);
        Route::get('/{document}', [DocumentController::class, 'show']);
        Route::patch('/{document}', [DocumentController::class, 'update']);
        Route::delete('/{document}', [DocumentController::class, 'destroy']);
        Route::get('/{document}/download', [DocumentController::class, 'download']);
        Route::get('/{document}/signed-urls', [DocumentController::class, 'getSignedUrls']);
        Route::post('/{document}/restore', [DocumentController::class, 'restore']);
        Route::post('/{document}/assign-category', [DocumentController::class, 'assignCategory']);
        Route::delete('/{document}/remove-category', [DocumentController::class, 'removeCategory']);
        Route::post('/{document}/process-ai', [DocumentController::class, 'processWithAI']);
    });
    
    // Category Management
    Route::prefix('category-groups')->group(function () {
        Route::get('/', [CategoryGroupController::class, 'index']);
        Route::post('/', [CategoryGroupController::class, 'store']);
        Route::get('/{categoryGroup}', [CategoryGroupController::class, 'show']);
        Route::patch('/{categoryGroup}', [CategoryGroupController::class, 'update']);
        Route::delete('/{categoryGroup}', [CategoryGroupController::class, 'destroy']);
        Route::patch('/{categoryGroup}/toggle', [CategoryGroupController::class, 'toggle']);
        Route::post('/reorder', [CategoryGroupController::class, 'reorder']);
    });
    
    Route::prefix('category-groups/{categoryGroup}/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::patch('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
        Route::patch('/{category}/toggle', [CategoryController::class, 'toggle']);
        Route::patch('/{category}/set-default', [CategoryController::class, 'setDefault']);
        Route::post('/reorder', [CategoryController::class, 'reorder']);
    });
    
});

// Public signed document routes (no authentication required)
Route::get('/documents/{document}/signed/{filename}', [SignedDocumentController::class, 'download'])
    ->name('documents.signed-download')
    ->middleware(['signed']);

