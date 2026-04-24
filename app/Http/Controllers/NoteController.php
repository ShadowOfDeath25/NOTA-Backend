<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class NoteController extends Controller
{
    /**
     * List all notes for the authenticated user.
     * Optionally filter by ?space_id=uuid.
     */
    #[Authorize('viewAny', Note::class)]
    public function index(Request $request, ?Space $space): JsonResponse
    {
        $q = Note::query();
        if ($space) {
            $q->where('space_id', $space->id);
        } else {
            $q->where('user_id', $request->user()->id);
        }
        $notes = $q
            ->latest()
            ->get();

        return response()->json(['data' => $notes]);
    }

    /**
     * Create a new note for the authenticated user.
     */
    #[Authorize('create', [Note::class, 'space'])]
    public function store(StoreNoteRequest $request, ?Space $space = null): JsonResponse
    {

        $note = Note::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $note], 201);
    }

    /**
     * Show a single note (must belong to the authenticated user).
     */
    public function show(Request $request, Note $note): JsonResponse
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['data' => $note]);
    }

    /**
     * Update a note (must belong to the authenticated user).
     */
    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $note->update($request->validated());

        return response()->json(['data' => $note]);
    }

    /**
     * Delete a note (must belong to the authenticated user).
     */
    public function destroy(Request $request, Note $note): JsonResponse
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted.']);
    }
}
