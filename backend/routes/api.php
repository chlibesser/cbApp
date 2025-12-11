<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Auth\LoginController;
use App\Infrastructure\Http\Controllers\Auth\RegisterController;
use App\Infrastructure\Http\Controllers\Auth\QuickLoginController;

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

