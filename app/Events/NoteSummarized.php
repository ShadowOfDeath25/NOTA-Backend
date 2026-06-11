<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteSummarized implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $userId,
        public readonly Note $summary
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->userId}"),
            new Channel("users.{$this->userId}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'note_id' => $this->summary->id,
            'title' => $this->summary->title,
            'space_id' => $this->summary->space_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'note.summarized';
    }
}
