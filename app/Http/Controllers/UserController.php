<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->id !== auth()->user()->id) {
            return response()->json(["message" => "unauthorized"], 403);
        }
        $user->update($request->validated());

        return response()->json(['user' => $user]);
    }
}
