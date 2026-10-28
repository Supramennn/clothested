<?php

namespace App\Livewire;

use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * FR-58/FR-62: the chat entry point on every page with the unread badge of
 * unanswered admin replies.
 */
class ChatButton extends Component
{
    #[Computed]
    public function unread(): int
    {
        return app(ChatService::class)->unreadForCustomer(Auth::user());
    }

    /**
     * Channel events refresh the badge without a full page load.
     */
    #[On('chat.message.received')]
    public function refreshUnread(int $chatSessionId = 0): void
    {
        unset($this->unread);
    }

    #[On('chat.read.updated')]
    public function clearUnread(int $chatSessionId = 0): void
    {
        unset($this->unread);
    }

    public function render(): View
    {
        return view('livewire.chat-button');
    }
}
