<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

// Protected routes - require authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Password management routes
    Route::apiResource('passwords', PasswordController::class);

    // Additional password routes
    Route::post('passwords/search', [PasswordController::class, 'search']);
    Route::post('passwords/{password}/decrypt', [PasswordController::class, 'decrypt'])->middleware('throttle:decrypt');
    Route::post('passwords/generate', [PasswordController::class, 'generatePassword']);

    // Security routes
    Route::prefix('security')->group(function () {
        Route::get('audit-logs', [\App\Http\Controllers\Api\SecurityController::class, 'auditLogs']);
        Route::get('suspicious-activities', [\App\Http\Controllers\Api\SecurityController::class, 'suspiciousActivities']);
        Route::get('login-history', [\App\Http\Controllers\Api\SecurityController::class, 'loginHistory']);
        Route::get('stats', [\App\Http\Controllers\Api\SecurityController::class, 'securityStats']);
        Route::post('report-incident', [\App\Http\Controllers\Api\SecurityController::class, 'reportIncident']);
    });
});

// Health check route
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Password Manager API'
    ]);
});
