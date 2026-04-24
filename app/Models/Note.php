<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'deleted_at',
        'space_id',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function space(): BelongsTo
    {
       return $this->belongsTo(Space::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
