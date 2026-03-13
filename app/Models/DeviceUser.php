<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DeviceUser extends Pivot
{
    protected $fillable = [
        'device_id',
        'user_id',
        'last_login',
    ];
}
