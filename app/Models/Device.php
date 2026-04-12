<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class Device extends Model
{
    use HasUuids;
    protected $fillable = [
        'type',
        'token',
    ];
}
