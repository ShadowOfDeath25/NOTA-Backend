<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure settings exist with defaults if null
        $settings = $user->settings ?? [
            'language' => 'english',
            'theme' => 'light',
            'email_notification' => 'on',
            'push_notification' => 'on',
            '2FA' => 'off',
        ];

        // Attach settings explicitly if we want to return it as part of the user object
        $user->settings = $settings;

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update generic user fields if provided
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        // Update settings if provided
        if (isset($validated['settings'])) {
            $currentSettings = $user->settings ?? [];
            $user->settings = array_merge($currentSettings, $validated['settings']);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }
}
