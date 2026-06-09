<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceUser extends Pivot
{
    use HasUuids,SoftDeletes;

    protected $fillable = [
        'device_id',
        'user_id',
        'last_login',
    ];
}
