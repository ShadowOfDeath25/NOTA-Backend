<?php

namespace App\Models;

use App\Observers\AssignsUUID;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AssignsUUID::class)]
class Space extends Model
{
    protected $fillable = [

        'name',
    ];
}
