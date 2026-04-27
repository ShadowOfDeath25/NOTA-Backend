<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invite extends Model
{
    use HasUuids, softDeletes;

    protected $fillable = [
        'url',
        'space_id',
        'user_id',
        'single_use',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'single_use' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }


    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
    // I don't know if this will be needed but anyway
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public static function generateUrl(): string
    {
        return Str::random(64);
    }
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    public function isUsed(): bool
    {
        return $this->trashed();
    }
//    public function isSingleUse(): bool
//    {
//        return $this->single_use;
//    }
    public function consume(): void
    {
        //if ($this->single_use) {
            $this->delete();
       // }
    }
    public function scopeValid($query): void
    {
        $query->whereNull('deleted_at')
              ->where('expires_at', '>', now());
    }

}
