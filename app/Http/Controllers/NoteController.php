<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * List all notes for the authenticated user.
     * Optionally filter by ?space_id=uuid.
     */
    public function index(Request $request): JsonResponse
    {
        $notes = Note::query()
            ->where('user_id', $request->user()->id)
            ->when($request->query('space_id'), fn ($q, $spaceId) => $q->where('space_id', $spaceId))
            ->latest()
            ->get();

        return response()->json(['data' => $notes]);
    }

    /**
     * Create a new note for the authenticated user.
     */
    public function store(StoreNoteRequest $request): JsonResponse
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

