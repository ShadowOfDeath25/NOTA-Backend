<?php

use App\Events\NoteSummarized;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SpaceUserController;
use App\Http\Controllers\UserController;
use App\Models\Note;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

$limiter = config('fortify.limiters.login');
$twoFactorLimiter = config('fortify.limiters.two-factor');
$verificationLimiter = config('fortify.limiters.verification', '6,1');

Route::middleware('guest:web')->group(function () {
    $limiter = config('fortify.limiters.login');
    $twoFactorLimiter = config('fortify.limiters.two-factor');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            $limiter ? 'throttle:' . $limiter : null,
        ]));
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);

    if (Features::twoFactorAuthentication()) {
        Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                $twoFactorLimiter ? 'throttle:' . $twoFactorLimiter : null,
            ]));
    }
});

Route::middleware('auth:sanctum')->group(function () {
    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('user.update');
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/revoke-other-tokens', [AuthController::class, 'revokeOtherTokens']);
    Route::post('/auth/confirm-password', [AuthController::class, 'confirmPassword']);

    Route::get('/user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show']);
    Route::get('/user/confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('/user/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/user/profile-information', [ProfileInformationController::class, 'update']);
    Route::put('/user/password', [PasswordController::class, 'update']);

    if (Features::enabled(Features::emailVerification())) {
        Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
            ->middleware(['signed', 'throttle:' . $verificationLimiter]);
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['throttle:' . $verificationLimiter]);
    }

    if (Features::twoFactorAuthentication()) {
        Route::post('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store']);
        Route::post('/user/confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store']);
        Route::delete('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy']);
        Route::get('/user/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show']);
        Route::get('/user/two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show']);
        Route::get('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'index']);
        Route::post('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'store']);
    }
    Route::get('notes/favorites', [NoteController::class, 'favorites'])->name("notes.favorites");
    Route::get('notes/trashed', [NoteController::class, 'trashed']);
    Route::post('notes/{note}/restore', [NoteController::class, 'restore'])->withTrashed();
    Route::delete('notes/{note}/force', [NoteController::class, 'forceDelete'])->withTrashed();


    Route::post('notes/{note}/favorites', [NoteController::class, 'addToFavorites'])->name("notes.favorites.add");
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('spaces.notes', NoteController::class)->shallow();
    Route::apiResource('spaces', SpaceController::class);
    Route::post('spaces/{space}/invites', [InviteController::class, 'store']);
    Route::post('invites/{url}/accept', [InviteController::class, 'accept']);
    Route::put('spaces/{space}/users/{user}', [SpaceUserController::class, 'update']);
    Route::post('/summarize', [NoteController::class, 'summarizeText']);
    Route::get('notes/{note}/summarize', [NoteController::class, 'summarize']);
    Route::post('/notes/read-pdf', [NoteController::class, 'fromPDF']);

    Route::get('fire-event', function (\Illuminate\Http\Request $request) {
        NoteSummarized::dispatch($request->user()->id, Note::inRandomOrder()->first());
    });
});
