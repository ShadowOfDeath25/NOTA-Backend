<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SpaceUser extends Pivot
{
    use HasUuids;

    protected $fillable = [
        'space_id',
        'user_id',
        'is_owner',
        'joined_at',
    ];
}
