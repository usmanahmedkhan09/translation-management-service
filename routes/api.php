<?php

use App\Http\Controllers\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Specific routes first (before resourceful routes)
Route::get('/translations/export', [TranslationController::class, 'export']);
Route::get('/translations/locales', [TranslationController::class, 'locales']);
Route::get('/translations/tags', [TranslationController::class, 'tags']);
Route::get('/search/translations', [TranslationController::class, 'index']);

// Translation CRUD routes (resourceful routes last)
Route::apiResource('translations', TranslationController::class); 