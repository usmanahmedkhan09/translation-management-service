<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    
    // Translation routes (specific routes first)
    Route::get('/translations/export', [TranslationController::class, 'export']);
    Route::get('/translations/locales', [TranslationController::class, 'locales']);
    Route::get('/translations/tags', [TranslationController::class, 'tags']);
    Route::get('/search/translations', [TranslationController::class, 'index']);
    
    // Translation CRUD routes (resourceful routes last)
    Route::apiResource('translations', TranslationController::class);
}); 