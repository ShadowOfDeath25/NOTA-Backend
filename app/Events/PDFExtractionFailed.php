<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PDFExtractionFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $userId,
    ) {
        //
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'PDF extraction failed, try again later.',
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->userId}"),
            new Channel("users.{$this->userId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'pdf.extraction_failed';
    }
}
