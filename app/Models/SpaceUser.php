<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SpaceUser extends Pivot
{
    protected $fillable = [
        'space_id',
        'user_id',
        'is_owner',
        'joined_at',
    ];
}
