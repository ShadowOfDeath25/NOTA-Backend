<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadySpaceMemberException;
use App\Exceptions\InviteAlreadyUsedException;
use App\Exceptions\InviteExpiredException;
use App\Http\Requests\CreateInviteRequest;
use App\Http\Resources\InviteResource;
use App\Http\Resources\SpaceResource;
use App\Models\Invite;
use App\Models\Space;
use App\Services\InviteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function __construct(private readonly InviteService $inviteService)
    {}

    /**
     * Display a listing of the resource.
     */
//    public function index()
//    {
//        //
//    }

    /**
     * Show the form for creating a new resource.
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateInviteRequest $request, Space $space): JsonResponse
    {
        $invite = $this->inviteService->create($space, $request->user());
        return response()->json([
            'message' => 'Invite created successfully.',
            'data' => new InviteResource($invite),
        ], 201);
    }

    public function accept(Request $request, string $url): JsonResponse
    {
        try {
            $space = $this->inviteService->accept($url, $request->user());
            return response()->json([
                'message' => 'Space joined successfully.',
                'data' => new SpaceResource($space),
            ]);

        } catch (InviteAlreadyUsedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InviteExpiredException $e){
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (AlreadySpaceMemberException $e){
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (ModelNotFoundException $e){
            return response()->json(['message' => 'Invite link not found.'], 404);
        }
    }

    /**
     * Display the specified resource.
     */

    // fuck


    // will test tomorrow
}
