<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FavoriteNote extends Pivot
{
    protected $table = "favorite_notes";
    protected $fillable = [
        'user_id',
        'note_id',
    ];
}
