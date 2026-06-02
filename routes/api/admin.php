<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CaptainController as AdminCaptainController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PointController as AdminPointController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Admin\RouteTimeController as AdminRouteTimeController;
use App\Http\Controllers\Admin\TermController as AdminTermController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TimeController as AdminTimeController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\TripController as AdminTripController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ContactUsController as AdminContactUsController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::post('register', [AdminAuthController::class, 'register']);
Route::post('login', [AdminAuthController::class, 'login']);
Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('forgot-password/verify-otp', [AdminAuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:12,1');
Route::post('forgot-password/reset', [AdminAuthController::class, 'resetPasswordWithToken'])->middleware('throttle:6,1');

Route::get('cars', [AdminCarController::class, 'index']);
Route::get('cars/{car}', [AdminCarController::class, 'show']);

Route::get('points', [AdminPointController::class, 'index']);
Route::get('points/{point}', [AdminPointController::class, 'show']);
Route::get('points/route/{routeId}', [AdminPointController::class, 'getPointsByRouteId']);

Route::get('times', [AdminTimeController::class, 'index']);
Route::get('times/{time}', [AdminTimeController::class, 'show']);

Route::get('terms', [AdminTermController::class, 'index']);
Route::get('terms/{term}', [AdminTermController::class, 'show']);

Route::get('notifications', [AdminNotificationController::class, 'index']);
Route::get('notifications/{notification}', [AdminNotificationController::class, 'show']);

// calculate price for reservation
Route::post('calculatePrice', [AdminReservationController::class, 'calculatePrice']);

Route::get('contact-us', [AdminContactUsController::class, 'index']);
Route::get('contact-us/{id}', [AdminContactUsController::class, 'show']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsAdmin::class])->group(function (): void {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);

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

    // Route::get('terms/all', [AdminTermController::class, 'indexAll']);

    Route::post('terms', [AdminTermController::class, 'store']);
    Route::put('terms/{term}', [AdminTermController::class, 'update']);
    Route::patch('terms/{term}', [AdminTermController::class, 'update']);
    Route::delete('terms/{term}', [AdminTermController::class, 'destroy']);

    Route::get('notificationsAll', [AdminNotificationController::class, 'indexAll']);

    Route::get('notificationsFiredByUserType', [AdminNotificationController::class, 'firedByUserType']);
    Route::post('notifications/{notification}/fire', [AdminNotificationController::class, 'fireNotification'])->whereNumber('notification');

    Route::post('notifications', [AdminNotificationController::class, 'store']);
    Route::put('notifications/{notification}', [AdminNotificationController::class, 'update']);
    Route::patch('notifications/{notification}', [AdminNotificationController::class, 'update']);
    Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy']);

    Route::get('reservations', [AdminReservationController::class, 'index']);
    Route::get('reservations/groups', [AdminReservationController::class, 'groups']);
    Route::patch('reservations/{reservation}/status', [AdminReservationController::class, 'updateStatus'])->whereNumber('reservation');

    Route::get('route-times', [AdminRouteTimeController::class, 'index']);
    Route::post('route-times', [AdminRouteTimeController::class, 'store']);
    Route::get('route-times/{routeTime}', [AdminRouteTimeController::class, 'show'])->whereNumber('routeTime');
    Route::put('route-times/{routeTime}', [AdminRouteTimeController::class, 'update'])->whereNumber('routeTime');
    Route::patch('route-times/{routeTime}', [AdminRouteTimeController::class, 'update'])->whereNumber('routeTime');
    Route::delete('route-times/{routeTime}', [AdminRouteTimeController::class, 'destroy'])->whereNumber('routeTime');

    Route::get('reports', [AdminReportController::class, 'index']);
    Route::get('reports/{report}', [AdminReportController::class, 'show'])->whereNumber('report');
    Route::patch('reports/{report}/reply', [AdminReportController::class, 'reply'])->whereNumber('report');

    Route::get('tickets', [AdminTicketController::class, 'index']);
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->whereNumber('ticket');
    Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->whereNumber('ticket');
    Route::patch('tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->whereNumber('ticket');
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
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::put('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::patch('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::delete('users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');

    Route::get('permissions', [AdminRoleController::class, 'getPermissions']);
    Route::get('companies', [AdminUserController::class, 'companiesList']);

    Route::post('contact-us', [AdminContactUsController::class, 'store']);
    Route::put('contact-us/{id}', [AdminContactUsController::class, 'update'])->whereNumber('id');
    Route::patch('contact-us/{id}', [AdminContactUsController::class, 'update'])->whereNumber('id');
    Route::delete('contact-us/{id}', [AdminContactUsController::class, 'destroy'])->whereNumber('id');

    Route::get('transactions', [AdminTransactionController::class, 'index']);
    Route::get('transactions/{transaction}', [AdminTransactionController::class, 'show'])->whereNumber('transaction');
    Route::post('transactions', [AdminTransactionController::class, 'store']);
    Route::put('transactions/{transaction}/status', [AdminTransactionController::class, 'updateStatus'])->whereNumber('transaction');
    Route::get('transactions/client/{clientId}', [AdminTransactionController::class, 'getTransactionByClientId'])->whereNumber('clientId');
    
    
    Route::get('clients/phone/{phone}', [AdminUserController::class, 'getClientByPhoneNumber'])->where('phone', '[0-9]+');
    Route::get('captains/phone/{phone}', [AdminCaptainController::class, 'getCaptainByPhoneNumber'])->where('phone', '[0-9]+');
    Route::post('captains/{id}/updateBalance', [AdminCaptainController::class, 'updateBalance'])->whereNumber('id');
});
