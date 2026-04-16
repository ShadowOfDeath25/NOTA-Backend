<?php

namespace App\Contracts\Responses;

use App\Helpers\ClientDetector;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(
        private readonly ClientDetector $clientDetector
    ) {}

    public function toResponse($request): JsonResponse
    {
        $user = $request->user();

        if ($this->clientDetector->isMobile() && $user instanceof User) {
            $token = $user->createToken('auth-token');

            return response()->json([
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token->plainTextToken,
            ], 201);
        }

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }
}
