<?php

namespace App\Services;

use App\Helpers\ClientDetector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\NewAccessToken;

final class AuthService
{
    public function __construct(
        private readonly ClientDetector $clientDetector
    ) {}

    public function createTokenForUser(User $user, string $tokenName = 'auth-token'): NewAccessToken
    {
        return $user->createToken($tokenName);
    }

    public function revokeAllTokensForUser(User $user): void
    {
        $user->tokens()->delete();
    }

    public function revokeCurrentToken(Request $request, string $tokenName = 'auth-token'): void
    {
        $request->user()?->tokens()
            ->where('name', $tokenName)
            ->delete();
    }

    public function isAuthenticatedViaToken(): bool
    {
        return $this->clientDetector->isMobile();
    }

    public function isAuthenticatedViaSession(): bool
    {
        return $this->clientDetector->isSPA();
    }

    public function logout(Request $request): void
    {
        if ($this->isAuthenticatedViaToken()) {
            $this->revokeCurrentToken($request);
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    public function getAuthenticatedUser(Request $request): ?User
    {
        return $request->user();
    }
}
