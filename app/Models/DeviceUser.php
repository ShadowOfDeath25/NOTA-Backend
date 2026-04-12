<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DeviceUser extends Pivot
{
    use HasUuids;
    protected $fillable = [
        'device_id',
        'user_id',
        'last_login',
    ];
}
