<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Contracts\Responses\LoginResponse;
use App\Contracts\Responses\LogoutResponse;
use App\Contracts\Responses\RegisterResponse;
use App\Helpers\ClientDetector;
use App\Services\AuthService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClientDetector::class, function ($app) {
            return new ClientDetector(request());
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService($app->make(ClientDetector::class));
        });

        $this->app->singleton(LoginResponse::class, function ($app) {
            return new LoginResponse($app->make(ClientDetector::class));
        });

        $this->app->singleton(LogoutResponse::class, function ($app) {
            return new LogoutResponse($app->make(AuthService::class));
        });

        $this->app->singleton(RegisterResponse::class, function ($app) {
            return new RegisterResponse($app->make(ClientDetector::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        $this->app->bind(\Laravel\Fortify\Contracts\LoginResponse::class, LoginResponse::class);
        $this->app->bind(\Laravel\Fortify\Contracts\LogoutResponse::class, LogoutResponse::class);
        $this->app->bind(\Laravel\Fortify\Contracts\RegisterResponse::class, RegisterResponse::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $frontendUrl = config('app.frontend_url');

            return $frontendUrl.'/verify-email/'.$notifiable->getKey().'/'.sha1($notifiable->getEmailForVerification());
        });

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontendUrl = config('app.frontend_url');

            return $frontendUrl.'/reset-password?token='.$token.'&email='.$notifiable->getEmailForPasswordReset();
        });
    }
}
