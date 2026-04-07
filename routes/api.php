<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Captain\AuthController as CaptainAuthController;
use App\Http\Controllers\Client\AuthController as ClientAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'type.admin'])->group(function (): void {
        Route::get('profile', [AdminAuthController::class, 'profile']);
        Route::put('profile', [AdminAuthController::class, 'updateProfile']);
        Route::post('logout', [AdminAuthController::class, 'logout']);
    });
});

Route::prefix('captain')->group(function (): void {
    Route::post('register', [CaptainAuthController::class, 'register']);
    Route::post('login', [CaptainAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'type.captain'])->group(function (): void {
        Route::get('profile', [CaptainAuthController::class, 'profile']);
        Route::put('profile', [CaptainAuthController::class, 'updateProfile']);
        Route::post('logout', [CaptainAuthController::class, 'logout']);
    });
});

Route::prefix('client')->group(function (): void {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login', [ClientAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'type.client'])->group(function (): void {
        Route::get('profile', [ClientAuthController::class, 'profile']);
        Route::put('profile', [ClientAuthController::class, 'updateProfile']);
        Route::post('logout', [ClientAuthController::class, 'logout']);
    });
});
