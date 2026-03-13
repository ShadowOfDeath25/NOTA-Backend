<?php

namespace App\Models;

use App\Observers\AssignsUUID;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AssignsUUID::class)]
class Note extends Model
{
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'deleted_at',
        'space_id',
    ];
}
