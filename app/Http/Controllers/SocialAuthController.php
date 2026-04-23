<?php

namespace App\Http\Controllers;

use App\Helpers\ClientDetector;
use App\Services\AuthService;
use App\Services\SocialiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly AuthService $authService,
        private readonly ClientDetector $clientDetector
    ) {}

    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $redirect = $this->socialiteService->redirectToProvider($provider);

            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }

            return response()->json([
                'redirect_url' => $redirect->getTargetUrl(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => "Failed to redirect to {$provider}",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function callback(string $provider): JsonResponse|RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $user = $this->socialiteService->handleProviderCallback($provider);

            if (! $user) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Failed to authenticate with '.ucfirst($provider),
                    ], 401);
                }

                $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

                return redirect("{$frontendUrl}/login?error=auth_failed");
            }

            if ($this->clientDetector->isMobile()) {
                $token = $this->authService->createTokenForUser($user, "{$provider}-auth-token");

                return response()->json([
                    'message' => 'Authentication successful',
                    'user' => $user,
                    'token' => $token->plainTextToken,
                ]);
            }

            $this->socialiteService->loginUser($user);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

            return redirect("{$frontendUrl}/dashboard?provider={$provider}");
        } catch (Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication failed',
                    'error' => $e->getMessage(),
                ], 500);
            }

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

            return redirect("{$frontendUrl}/login?error=".urlencode($e->getMessage()));
        }
    }

    private function validateProvider(string $provider): void
    {
        $validProviders = ['google', 'github', 'facebook', 'twitter', 'linkedin'];

        if (! in_array($provider, $validProviders)) {
            abort(400, "Invalid provider: {$provider}");
        }
    }
}
