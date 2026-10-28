<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sequence 5.4: a fresh chat line pushes over the private session channel so
 * both sides see it without reloading the page.
 */
class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly ChatMessage $message) {}

    /**
     * FR-60: both participants listen on chat.{sessionId}.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->message->chat_session_id)];
    }

    /**
     * The browser listener name, kept free of the class namespace.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * The member data the frontend relays to the Livewire components.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_session_id' => $this->message->chat_session_id,
            'sender_role' => $this->message->sender_role,
            'body' => $this->message->body,
            'sent_at' => $this->message->sent_at?->toISOString(),
        ];
    }
}
