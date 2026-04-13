<?php

use App\Http\Controllers\Captain\AuthController as CaptainAuthController;
use App\Http\Controllers\UserMediaController;
use App\Http\Middleware\EnsureUserIsCaptain;
use Illuminate\Support\Facades\Route;

Route::post('register', [CaptainAuthController::class, 'register']);
Route::post('login', [CaptainAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsCaptain::class])->group(function (): void {
    Route::get('profile', [CaptainAuthController::class, 'profile']);
    Route::put('profile', [CaptainAuthController::class, 'updateProfile']);
    Route::post('logout', [CaptainAuthController::class, 'logout']);

    Route::post('media/avatar', [UserMediaController::class, 'storeAvatar']);
    Route::delete('media/avatar', [UserMediaController::class, 'destroyAvatar']);
    Route::post('media/documents', [UserMediaController::class, 'storeDocument']);
    Route::delete('media/documents/{media}', [UserMediaController::class, 'destroyDocument']);
});
