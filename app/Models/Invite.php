<?php

namespace App\Models;

use App\Observers\AssignsUUID;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
#[ObservedBy(AssignsUUID::class)]
class Invite extends Model
{
    protected $fillable = [
        'url',
        'space_id',
        'user_id',
        'single_use',
        'expires_at',
    ];
}
