<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpdateSpaceUserRequest;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SpaceUserController extends Controller
{
    /**
     * Update the role of a user in a space.
     */
    public function update(UpdateSpaceUserRequest $request, Space $space, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();

        $authUserRole = $space->userRole($authenticatedUser);
        $targetUserRole = $space->userRole($user);

        if (! $targetUserRole) {
            return response()->json(['message' => 'User not found in this space.'], 404);
        }

        // Only ADMIN and OWNER can change roles
        if (! in_array($authUserRole, [Role::OWNER->value, Role::ADMIN->value])) {
            abort(403, 'Forbidden');
        }

        // ADMIN cannot change OWNER's role
        if ($authUserRole === Role::ADMIN->value && $targetUserRole === Role::OWNER->value) {
            abort(403, 'Admin cannot change owner role.');
        }

        $space->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        return response()->json(['message' => 'User role updated successfully.']);
    }
}
