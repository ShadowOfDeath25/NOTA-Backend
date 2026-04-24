<?php

namespace App\Models;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function userRole(User $user)
    {
        return SpaceUser::where('user_id', $user->id)->first()->role;
    }
}
