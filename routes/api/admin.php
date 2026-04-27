<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CaptainController as AdminCaptainController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PointController as AdminPointController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Admin\TermController as AdminTermController;
use App\Http\Controllers\Admin\TimeController as AdminTimeController;
use App\Http\Controllers\Admin\TripController as AdminTripController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::post('register', [AdminAuthController::class, 'register']);
Route::post('login', [AdminAuthController::class, 'login']);

Route::get('cars', [AdminCarController::class, 'index']);
Route::get('cars/{car}', [AdminCarController::class, 'show']);

Route::get('points', [AdminPointController::class, 'index']);
Route::get('points/{point}', [AdminPointController::class, 'show']);

Route::get('times', [AdminTimeController::class, 'index']);
Route::get('times/{time}', [AdminTimeController::class, 'show']);

Route::get('terms', [AdminTermController::class, 'index']);
Route::get('terms/{term}', [AdminTermController::class, 'show']);

Route::get('notifications', [AdminNotificationController::class, 'index']);
Route::get('notifications/{notification}', [AdminNotificationController::class, 'show']);

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

    Route::middleware(['permission:routes.view'])->group(function (): void {
        Route::get('routes', [AdminRouteController::class, 'index']);
        Route::get('routes/{route}', [AdminRouteController::class, 'show']);
    });

    Route::middleware(['permission:routes.manage'])->group(function (): void {
        Route::get('AllRoutes', [AdminRouteController::class, 'indexAll']);
        Route::post('routes', [AdminRouteController::class, 'store']);
        Route::put('routes/{route}', [AdminRouteController::class, 'update']);
        Route::delete('routes/{route}', [AdminRouteController::class, 'destroy']);
    });

    Route::post('points', [AdminPointController::class, 'store']);
    Route::put('points/{point}', [AdminPointController::class, 'update']);
    Route::delete('points/{point}', [AdminPointController::class, 'destroy']);

    Route::get('times/all', [AdminTimeController::class, 'indexAll']);

    Route::post('times', [AdminTimeController::class, 'store']);
    Route::put('times/{time}', [AdminTimeController::class, 'update']);
    Route::delete('times/{time}', [AdminTimeController::class, 'destroy']);

    Route::get('terms/all', [AdminTermController::class, 'indexAll']);

    Route::post('terms', [AdminTermController::class, 'store']);
    Route::put('terms/{term}', [AdminTermController::class, 'update']);
    Route::patch('terms/{term}', [AdminTermController::class, 'update']);
    Route::delete('terms/{term}', [AdminTermController::class, 'destroy']);

    Route::get('notifications/all', [AdminNotificationController::class, 'indexAll']);

    Route::get('notifications/fired/by-user', [AdminNotificationController::class, 'firedByUserType']);
    Route::post('notifications/{notification}/fire', [AdminNotificationController::class, 'fireNotification'])->whereNumber('notification');

    Route::post('notifications', [AdminNotificationController::class, 'store']);
    Route::put('notifications/{notification}', [AdminNotificationController::class, 'update']);
    Route::patch('notifications/{notification}', [AdminNotificationController::class, 'update']);
    Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy']);

    Route::get('reservations', [AdminReservationController::class, 'index']);
    Route::patch('reservations/{reservation}/status', [AdminReservationController::class, 'updateStatus'])->whereNumber('reservation');

    Route::get('reports', [AdminReportController::class, 'index']);
    Route::get('reports/{report}', [AdminReportController::class, 'show'])->whereNumber('report');
    Route::patch('reports/{report}/reply', [AdminReportController::class, 'reply'])->whereNumber('report');
    Route::get('trips', [AdminTripController::class, 'index']);
    Route::get('trips/{trip}', [AdminTripController::class, 'show'])->whereNumber('trip');
    Route::post('trips', [AdminTripController::class, 'store']);
    Route::put('trips/{trip}', [AdminTripController::class, 'update'])->whereNumber('trip');
    Route::patch('trips/{trip}', [AdminTripController::class, 'update'])->whereNumber('trip');
    Route::delete('trips/{trip}', [AdminTripController::class, 'destroy'])->whereNumber('trip');

    Route::get('users/blocklist', [AdminUserController::class, 'blocklist']);
    Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])->whereNumber('id');
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::get('users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
    Route::put('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::patch('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::delete('users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');
    

    Route::get('permissions', [AdminRoleController::class, 'getPermissions']);
    Route::get('companies', [AdminUserController::class, 'companiesList']);
});
