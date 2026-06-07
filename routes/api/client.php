<?php

use App\Http\Controllers\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Client\FeedbackController as ClientFeedbackController;
use App\Http\Controllers\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Client\ReportController as ClientReportController;
use App\Http\Controllers\Client\ReservationController as ClientReservationController;
use App\Http\Controllers\Client\RouteController;
use App\Http\Controllers\Client\TermController as ClientTermController;
use App\Http\Controllers\Client\TicketController as ClientTicketController;
use App\Http\Controllers\Client\TransactionController as ClientTransactionController;
use App\Http\Controllers\Client\TripController as ClientTripController;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Support\Facades\Route;

Route::post('register', [ClientAuthController::class, 'register']);
Route::post('login', [ClientAuthController::class, 'login']);
Route::post('forgot-password', [ClientAuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('forgot-password/verify-otp', [ClientAuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:12,1');
Route::post('forgot-password/reset', [ClientAuthController::class, 'resetPasswordWithToken'])->middleware('throttle:6,1');
Route::get('routes', [RouteController::class, 'index']);
Route::get('routes/{routeId}/points', [RouteController::class, 'getPoints']);
Route::get('terms', [ClientTermController::class, 'index']);
Route::get('getRouteTimes/{routeId}', [RouteController::class, 'getRouteTimes']);
Route::get('getRouteTimesByTimeId/{timeId}', [RouteController::class, 'getRouteTimesByTimeId']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsClient::class])->group(function (): void {
    Route::get('profile', [ClientAuthController::class, 'profile']);
    Route::put('profile', [ClientAuthController::class, 'updateProfile']);
    Route::post('logout', [ClientAuthController::class, 'logout']);

    Route::get('notifications', [ClientNotificationController::class, 'index']);
    Route::patch('notifications/{deliveryId}/markAsRead', [ClientNotificationController::class, 'markAsRead']);
    Route::patch('notifications/markAllAsRead', [ClientNotificationController::class, 'markAllAsRead']);

    Route::get('reservations', [ClientReservationController::class, 'index']);
    Route::post('reservations', [ClientReservationController::class, 'store']);
    Route::patch('reservations/{reservation}/cancel', [ClientReservationController::class, 'cancel']);
    Route::delete('reservations/{reservation}', [ClientReservationController::class, 'destroy']);

    Route::get('trips', [ClientTripController::class, 'index']);
    Route::post('trips/{reservation}/captain-rating', [ClientTripController::class, 'rateCaptain'])->whereNumber('reservation');
    Route::get('reports', [ClientReportController::class, 'index']);
    Route::get('trips/{reservation}', [ClientTripController::class, 'show'])->whereNumber('reservation');

    Route::post('feedbacks', [ClientFeedbackController::class, 'store']);

    Route::get('tickets', [ClientTicketController::class, 'index']);
    Route::post('tickets', [ClientTicketController::class, 'store']);
    Route::post('tickets/{ticket}/messages', [ClientTicketController::class, 'addMessage'])->whereNumber('ticket');
    Route::get('tickets/{ticket}', [ClientTicketController::class, 'show'])->whereNumber('ticket');
    Route::put('tickets/{ticket}', [ClientTicketController::class, 'update'])->whereNumber('ticket');
    Route::patch('tickets/{ticket}', [ClientTicketController::class, 'update'])->whereNumber('ticket');
    Route::delete('tickets/{ticket}', [ClientTicketController::class, 'destroy'])->whereNumber('ticket');

    Route::get('transactions', [ClientTransactionController::class, 'index']);
    Route::post('transactions', [ClientTransactionController::class, 'store']);
});
