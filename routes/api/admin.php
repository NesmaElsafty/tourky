<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CaptainController as AdminCaptainController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::post('register', [AdminAuthController::class, 'register']);
Route::post('login', [AdminAuthController::class, 'login']);

Route::get('cars', [AdminCarController::class, 'index']);
Route::get('cars/{car}', [AdminCarController::class, 'show']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsAdmin::class])->group(function (): void {
    Route::get('profile', [AdminAuthController::class, 'profile']);
    Route::put('profile', [AdminAuthController::class, 'updateProfile']);
    Route::post('logout', [AdminAuthController::class, 'logout']);

    Route::apiResource('roles', AdminRoleController::class);

    Route::apiResource('captains', AdminCaptainController::class);

    Route::post('cars', [AdminCarController::class, 'store']);
    Route::put('cars/{car}', [AdminCarController::class, 'update']);
    Route::patch('cars/{car}', [AdminCarController::class, 'update']);
    Route::delete('cars/{car}', [AdminCarController::class, 'destroy']);
});
