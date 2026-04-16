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
                return response()->json([
                    'message' => 'Failed to authenticate with '.ucfirst($provider),
                ], 401);
            }

            if ($this->clientDetector->isMobile()) {
                $token = $this->authService->createTokenForUser($user, 'social-auth-token');

                return response()->json([
                    'message' => 'Authentication successful',
                    'user' => $user,
                    'token' => $token->plainTextToken,
                ]);
            }

            $this->socialiteService->loginUser($user);

            return response()->json([
                'message' => 'Authentication successful',
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ], 500);
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
