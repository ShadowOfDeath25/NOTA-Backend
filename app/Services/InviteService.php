<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AlreadySpaceMemberException;
use App\Exceptions\InviteAlreadyUsedException;
use App\Exceptions\InviteExpiredException;
use App\Models\Invite;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InviteService
{
    public function create(Space $space, User $user ): Invite
    {
        return Invite::create([
            'url' => Invite::generateUrl(),
         //   'single_use' => $singleUse,
            'space_id' => $space->id,
            'user_id' => $user->id,
            'expires_at' => now()->addDays(3),
        ]);
    }


    public function accept(string $url, User $user): Space
    {
        // I don't know what TF with transaction till now
        return DB::transaction(function () use ($url, $user) {
            $invite = Invite::withTrashed()
                ->where('url', $url)
                ->lockForUpdate()
                ->firstOrFail();

            if ($invite->isUsed()) {
                throw new  InviteAlreadyUsedException();
            }
            if ($invite->isExpired()) {
                throw new InviteExpiredException();
            }
            $space = $invite->space;
            if ($space->hasMember($user)) {
                throw new AlreadySpaceMemberException();
            }

            $space->users()->attach($user->id, [
                'role' => Role::VIEWER->value,
                'joined_at' => now(),
            ]);

            $invite->consume();
            return $space;
        });
    }
     public function revokeAllForSpace(Space $space): int
     {
         return Invite::where('space_id', $space->id)->delete();
     }
}
