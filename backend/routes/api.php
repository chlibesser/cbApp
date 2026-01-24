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
use App\Domains\Workflow\Http\Controllers\WorkflowController;
use App\Domains\Partner\Controllers\PartnerController;
use App\Infrastructure\Http\Controllers\TranslationController;
use App\Infrastructure\Http\Controllers\TableFilterController;
use App\Infrastructure\Http\Controllers\TableSettingsController;

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
        Route::post('/bulk-upload', [DocumentController::class, 'bulkStore']);
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
    
    // Workflow Management
    Route::prefix('workflows')->group(function () {
        Route::get('/', [WorkflowController::class, 'index']);
        Route::post('/', [WorkflowController::class, 'store']);
        Route::get('/{workflow}', [WorkflowController::class, 'show']);
        Route::patch('/{workflow}', [WorkflowController::class, 'update']);
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy']);
        Route::post('/{workflow}/execute', [WorkflowController::class, 'execute']);
    });
    
    // Partner Management
    Route::prefix('partners')->group(function () {
        Route::get('/', [PartnerController::class, 'index']);
        Route::post('/', [PartnerController::class, 'store']);
        Route::get('/search', [PartnerController::class, 'search']);
        Route::get('/statistics', [PartnerController::class, 'statistics']);
        Route::get('/needs-attention', [PartnerController::class, 'needsAttention']);
        Route::get('/{partner}', [PartnerController::class, 'show']);
        Route::patch('/{partner}', [PartnerController::class, 'update']);
        Route::delete('/{partner}', [PartnerController::class, 'destroy']);
    });
    
});

// Public signed document routes (no authentication required)
Route::get('/documents/{document}/signed/{filename}', [SignedDocumentController::class, 'download'])
    ->name('documents.signed-download')
    ->middleware(['signed']);

/*
|--------------------------------------------------------------------------
| Translation Routes
|--------------------------------------------------------------------------
| Routes for handling translations and locale management
| Public routes for getting translations, authenticated for user preferences
*/

// Public translation endpoints - no authentication required
Route::prefix('translations')->name('translations.')->group(function () {
    // GET /api/translations/locales - Get all available locales
    Route::get('locales', [TranslationController::class, 'getLocales'])->name('locales');
    
    // GET /api/translations/{locale} - Get all translations for locale
    Route::get('{locale}', [TranslationController::class, 'getAllTranslations'])
        ->name('all')
        ->where('locale', '[a-z]{2}');  // Only 2-letter locale codes
    
    // GET /api/translations/{locale}/{namespace} - Get namespace translations
    Route::get('{locale}/{namespace}', [TranslationController::class, 'getNamespaceTranslations'])
        ->name('namespace')
        ->where([
            'locale' => '[a-z]{2}',      // 2-letter locale codes
            'namespace' => '.*'          // Allow dots in namespace (admin.tenants)
        ]);
});

// Authenticated user locale routes - requires authentication
Route::middleware('auth:sanctum')->prefix('user')->name('user.')->group(function () {
    // GET /api/user/locale - Get user's current locale info
    Route::get('locale', [TranslationController::class, 'getUserLocale'])
        ->name('locale.get');
    
    // PUT /api/user/locale - Update user's locale preference
    Route::put('locale', [TranslationController::class, 'updateUserLocale'])
        ->name('locale.update');
});

// Development/Admin routes - should be protected in production
Route::middleware(['auth:sanctum', 'admin.only'])->group(function () {
    // DELETE /api/translations/cache - Clear translation cache
    Route::delete('translations/cache', [TranslationController::class, 'clearCache'])
        ->name('translations.cache.clear');
});

/*
|--------------------------------------------------------------------------
| Table Filter Routes
|--------------------------------------------------------------------------
| Routes for managing user-specific table filter presets
| All routes require authentication
*/

Route::middleware(['auth:sanctum'])->prefix('table-filters')->name('table-filters.')->group(function () {
    Route::get('/', [TableFilterController::class, 'index'])->name('index');
    Route::post('/', [TableFilterController::class, 'store'])->name('store');
    Route::patch('/{tableFilter}', [TableFilterController::class, 'update'])->name('update');
    Route::delete('/{tableFilter}', [TableFilterController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| Table Settings Routes
|--------------------------------------------------------------------------
| Routes for managing user-specific table settings
| (column order, column widths)
| All routes require authentication
*/

Route::middleware(['auth:sanctum'])->prefix('table-settings')->name('table-settings.')->group(function () {
    Route::get('/', [TableSettingsController::class, 'show'])->name('show');
    Route::put('/', [TableSettingsController::class, 'update'])->name('update');
    Route::delete('/', [TableSettingsController::class, 'destroy'])->name('destroy');
});