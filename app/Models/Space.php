<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
// مش عارف صح ولا لا
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

//
class Space extends Model
{
    use HasUuids;

    protected $fillable = [

        'name',
    ];

//    public function users(): BelongsToMany
//    {
//        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at');
//    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    //Only OWNER or ADMIN roles can create invite links
    public function userIsAdmin(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id',$user->id)
            ->wherePivot('role',[
                Role::ADMIN->value,
                Role::OWNER->value
            ])
            ->exists();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    public function invites()
    {
        return $this->hasMany(Invite::class);
    }
    public function userRole(User $user)
    {
        return SpaceUser::where('user_id', $user->id)->first()->role;
    }
}
