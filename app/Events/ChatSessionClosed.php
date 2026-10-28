<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * FR-63: an admin closed the conversation; the customer screen swaps to the
 * closed state and offers a fresh start.
 */
class ChatSessionClosed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly int $chatSessionId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->chatSessionId)];
    }

    public function broadcastAs(): string
    {
        return 'session.closed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'chat_session_id' => $this->chatSessionId,
        ];
    }
}
