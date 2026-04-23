<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'deleted_at',
        'space_id',
    ];
    protected $casts = [
        'content' => 'array',
    ];
}
