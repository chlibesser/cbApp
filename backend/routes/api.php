<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Auth\LoginController;
use App\Infrastructure\Http\Controllers\Auth\QuickLoginController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [LoginController::class, 'me'])->middleware('auth:sanctum');
    
    // Quick login for development
    Route::get('/quick-login', [QuickLoginController::class, 'index']);
    Route::post('/quick-login', [QuickLoginController::class, 'login']);
});