<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            //'id' => $this->id,
            'invite_url' => url('/join/'.$this->url),
            'expires_at' => $this->expires_at->toISOString(),
           // 'is_expired' => $this->isExpired(),
           // 'space_id' => $this->space_id,
        ];
    }
}
