<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\SummarizeNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use App\Models\Space;
use App\Services\SummaryService;
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

        $data = $request->validated();
        if (!isset($data['title'])) {
            $data['title'] = 'Untitled';
        }
        if ($space) {
            $data['space_id'] = $space->id;
        }
        $note = Note::create([
            ...$data,
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
            return response()->json(['message' => 'Unauthorized'], 401);
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
        $data = $request->validated();

        $note->update($data);

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

    public function summarize(Note $note, SummaryService $service)
    {
        $service->summarize($note->content, $note->title, $note->user_id, $note->space_id);
        return response()->json(["message" => "Summary in progress"], 202);
    }

    public function summarizeText(SummarizeNoteRequest $request, SummaryService $service)
    {
        $data = $request->validated();
        $service->summarize($data["content"], "Untitled Summary", auth()->user()->id, null);
        return response()->json(["message" => "Summary in progress"], 202);
    }
}
