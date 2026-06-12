<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Note extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'preview',
        'user_id',
        'deleted_at',
        'space_id',
    ];

    protected $casts = [
        'content' => 'array',
    ];
    protected $appends = [
        "is_favorite"
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFavorite(): Attribute
    {
        return Attribute::make(
            get: fn() => FavoriteNote::where('user_id', auth()->user()->id)
                ->where('note_id', $this->id)
                ->exists()

        );
    }
}
