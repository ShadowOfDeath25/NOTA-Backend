<?php

use App\Models\Note;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
}, ["middleware" => ["auth:sanctum"]]);

Broadcast::channel('note.{note}', function ($user, Note $note): bool {
    return $note && $user->can('view', $note);
});
