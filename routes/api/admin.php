<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CaptainController as AdminCaptainController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\PointController as AdminPointController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Admin\TimeController as AdminTimeController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::post('register', [AdminAuthController::class, 'register']);
Route::post('login', [AdminAuthController::class, 'login']);

Route::get('cars', [AdminCarController::class, 'index']);
Route::get('cars/{car}', [AdminCarController::class, 'show']);

Route::get('routes', [AdminRouteController::class, 'index']);
Route::get('routes/{route}', [AdminRouteController::class, 'show'])->whereNumber('route');

Route::get('points', [AdminPointController::class, 'index']);
Route::get('points/{point}', [AdminPointController::class, 'show'])->whereNumber('point');

Route::get('times', [AdminTimeController::class, 'index']);
Route::get('times/{time}', [AdminTimeController::class, 'show'])->whereNumber('time');

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

    Route::get('routes/all', [AdminRouteController::class, 'indexAll']);

    Route::post('routes', [AdminRouteController::class, 'store']);
    Route::put('routes/{route}', [AdminRouteController::class, 'update'])->whereNumber('route');
    Route::patch('routes/{route}', [AdminRouteController::class, 'update'])->whereNumber('route');
    Route::delete('routes/{route}', [AdminRouteController::class, 'destroy'])->whereNumber('route');

    Route::post('points', [AdminPointController::class, 'store']);
    Route::put('points/{point}', [AdminPointController::class, 'update'])->whereNumber('point');
    Route::patch('points/{point}', [AdminPointController::class, 'update'])->whereNumber('point');
    Route::delete('points/{point}', [AdminPointController::class, 'destroy'])->whereNumber('point');

    Route::get('times/all', [AdminTimeController::class, 'indexAll']);

    Route::post('times', [AdminTimeController::class, 'store']);
    Route::put('times/{time}', [AdminTimeController::class, 'update'])->whereNumber('time');
    Route::patch('times/{time}', [AdminTimeController::class, 'update'])->whereNumber('time');
    Route::delete('times/{time}', [AdminTimeController::class, 'destroy'])->whereNumber('time');
});
