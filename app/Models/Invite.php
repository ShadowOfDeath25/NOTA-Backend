<?php

namespace App\Models;

use App\Observers\AssignsUUID;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasUuids;
    protected $fillable = [
        'url',
        'space_id',
        'user_id',
        'single_use',
        'expires_at',
    ];
}
