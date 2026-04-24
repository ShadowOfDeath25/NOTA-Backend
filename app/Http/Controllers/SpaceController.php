<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Space\StoreSpaceRequest;
use App\Http\Requests\Space\UpdateSpaceRequest;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    /**
     * List all spaces the authenticated user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $spaces = $request->user()
            ->spaces()
            ->withCount('notes')
            ->latest()
            ->get();

        return response()->json(['data' => $spaces]);
    }

    /**
     * Create a new space and attach the creator as owner.
     */
    public function store(StoreSpaceRequest $request): JsonResponse
    {
        $space = Space::create($request->validated());

        $space->users()->attach($request->user()->id, [
            'role' => Role::OWNER->value,
            'joined_at' => now(),
        ]);

        return response()->json(['data' => $space], 201);
    }

    /**
     * Show a single space (only members may view).
     */
    public function show(Request $request, Space $space): JsonResponse
    {
        if (! $space->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $space->loadCount('notes');

        return response()->json(['data' => $space]);
    }

    /**
     * Update the space (only the owner/admin may update).
     */
    public function update(UpdateSpaceRequest $request, Space $space): JsonResponse
    {
        $userRole = $space->userRole($request->user());
        if (! contains([Role::OWNER, Role::ADMIN->value], $userRole)) {
            abort(403, 'Forbidden');
        }
        $space->update($request->validated());

        return response()->json(['data' => $space]);
    }

    /**
     * Delete the space (only the owner may delete).
     */
    public function destroy(Request $request, Space $space): JsonResponse
    {
        if (! $space->isOwnedBy($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $space->delete();

        return response()->json(['message' => 'Space deleted.']);
    }
}
