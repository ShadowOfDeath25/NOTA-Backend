<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::prefix('auth/social')->group(function () {
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/revoke-other-tokens', [AuthController::class, 'revokeOtherTokens']);
    Route::post('/auth/confirm-password', [AuthController::class, 'confirmPassword']);

    if (Features::twoFactorAuthentication()) {
        Route::get('/auth/two-factor/qr-code', [AuthController::class, 'getTwoFactorQrCode']);
        Route::get('/auth/two-factor/recovery-codes', [AuthController::class, 'getTwoFactorRecoveryCodes']);
        Route::post('/auth/two-factor/recovery-codes/regenerate', [AuthController::class, 'generateNewRecoveryCodes']);
    }
});

