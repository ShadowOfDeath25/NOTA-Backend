<?php

namespace App\Http\Controllers;

use App\Helpers\ClientDetector;
use App\Models\SpaceUser;
use App\Services\AuthService;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService    $authService,
        private readonly ClientDetector $clientDetector
    )
    {
    }

    public function user(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        if ($this->clientDetector->isMobile($request)) {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $this->authService->revokeCurrentToken($request);

            $token = $this->authService->createTokenForUser($user, 'refreshed-token');

            return response()->json([
                'message' => 'Token refreshed successfully',
                'token' => $token->plainTextToken,
            ]);
        }

        return response()->json(['message' => 'Token refresh not applicable for SPA mode'], 400);
    }

    public function revokeOtherTokens(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $currentToken = $request->bearerToken();

        $user->tokens()
            ->where('name', '!=', 'auth-token')
            ->delete();

        return response()->json([
            'message' => 'Other tokens revoked successfully',
        ]);
    }

    public function twoFactorChallenge(Request $request): JsonResponse
    {
        if ($this->clientDetector->isMobile($request)) {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return response()->json([
                'two_factor' => true,
                'two_factor_code' => null,
                'two_factor_recovery_codes' => null,
            ]);
        }

        return response()->json([
            'message' => 'Two-factor challenge required',
            'two_factor' => true,
        ]);
    }

    public function confirmPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:web'],
        ]);

        return response()->json([
            'message' => 'Password confirmed successfully',
        ]);
    }

    public function getTwoFactorQrCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->two_factor_confirmed_at) {
            return response()->json(['message' => 'Two-factor authentication not enabled'], 400);
        }

        return response()->json([
            'qr_code' => $user->twoFactorQrCodeSvg(),
            'secret' => $user->two_factor_secret,
        ]);
    }

    public function getTwoFactorRecoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $recoveryCodes = $user->recoveryCodes();

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function generateNewRecoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->generateNewRecoveryCodes();

        return response()->json([
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => [
                'required',
                'confirmed',
                Password::default(),
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

}
