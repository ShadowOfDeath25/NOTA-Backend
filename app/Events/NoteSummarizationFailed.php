<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteSummarizationFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $reason,
        public readonly string $noteTitle
    )
    {
        //
    }


    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->userId}");
    }

    public function broadcastWith(): array
    {
        return [

            'title' => $this->noteTitle,
            'reason' => $this->reason,
        ];
    }
    public function broadcastAs(): string
    {
        return 'note.summarization_failed';
    }
}
