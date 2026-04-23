<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Note;

# مش عارف صح ولا لا 
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
#
class Space extends Model
{
    use HasUuids;

    protected $fillable = [

        'name',
    ];
#############################
    public function users(): BelongsToMany
    {
       
        return $this->belongsToMany(User::class)->withPivot('is_owner', 'joined_at');
    }
###########################

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}

