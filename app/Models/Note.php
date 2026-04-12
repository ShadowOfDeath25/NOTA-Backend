<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class Note extends Model
{
    use HasUUids;
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'deleted_at',
        'space_id',
    ];
}
