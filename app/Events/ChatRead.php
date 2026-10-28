<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * FR-62: after one side reads the thread, both screens get the fresh unread
 * counters instead of recalculating alone.
 */
class ChatRead implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array{customer: int, admin: int}  $unread
     */
    public function __construct(
        public readonly int $chatSessionId,
        public readonly array $unread,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->chatSessionId)];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'chat_session_id' => $this->chatSessionId,
            'unread' => $this->unread,
        ];
    }
}
