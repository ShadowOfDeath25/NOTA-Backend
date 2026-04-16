<?php

namespace App\Contracts\Responses;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function toResponse($request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
