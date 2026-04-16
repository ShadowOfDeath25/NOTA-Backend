<?php

namespace App\Contracts\Responses;

use App\Helpers\ClientDetector;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private readonly ClientDetector $clientDetector
    ) {}

    public function toResponse($request): JsonResponse
    {
        if ($this->clientDetector->isMobile()) {
            $token = $request->user()->createToken('auth-token');

            return response()->json([
                'message' => 'Login successful',
                'user' => $request->user(),
                'token' => $token->plainTextToken,
            ]);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $request->user(),
        ]);
    }
}
