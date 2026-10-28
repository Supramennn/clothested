<?php

namespace App\Livewire\Admin;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-61/FR-62: the admin chat panel. Open sessions on the left, one thread on
 * the right, and the first reply claims the session.
 */
#[Layout('layouts.app')]
#[Title('Chat panel · ClothesTed')]
class AdminChatPanel extends Component
{
    public ?int $sessionId = null;

    public string $draft = '';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function sessions(): Collection
    {
        return app(ChatService::class)->openSessionOverview();
    }

    /**
     * The thread in view; a closed session stays readable for the history.
     */
    #[Computed]
    public function conversation(): ?ChatSession
    {
        return $this->sessionId === null
            ? null
            : ChatSession::query()->with(['user', 'order'])->find($this->sessionId);
    }

    /**
     * FR-59: last 50 lines of the thread, oldest first.
     *
     * @return Collection<int, ChatMessage>
     */
    #[Computed]
    public function history(): Collection
    {
        $conversation = $this->conversation;

        return $conversation === null
            ? collect()
            : app(ChatService::class)->lastMessages($conversation);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'draft' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * FR-61: pick a session from the list; opening it marks the customer
     * lines read.
     */
    public function selectSession(ChatService $chat, int $chatSessionId): void
    {
        $session = ChatSession::query()->where('status', 'open')->find($chatSessionId);

        if ($session === null) {
            return;
        }

        $this->sessionId = $session->id;
        $this->reset('draft');
        $this->resetErrorBag();

        $chat->markRead($session, Auth::user());

        $this->dispatch('chat.subscribe', chatSessionId: $session->id);

        unset($this->sessions, $this->conversation, $this->history);
    }

    /**
     * FR-62: mark the currently visible thread as read.
     */
    public function markRead(ChatService $chat): void
    {
        $conversation = $this->conversation;

        if ($conversation === null) {
            return;
        }

        $chat->markRead($conversation, Auth::user());

        unset($this->history, $this->sessions);
    }

    /**
     * FR-61: sending is the claim; the service sets admin_id on the first
     * admin line while a lock keeps the claim single.
     */
    public function send(ChatService $chat): void
    {
        $this->validate();

        $chat->sendMessage($this->conversation, 'admin', $this->draft, Auth::user());

        $this->reset('draft');
        $this->resetErrorBag();

        unset($this->history, $this->sessions);
    }

    /**
     * FR-63: closing ends the thread; the customer can start a fresh one.
     */
    public function close(ChatService $chat): void
    {
        $conversation = $this->conversation;

        if ($conversation === null) {
            return;
        }

        $chat->close($conversation, Auth::user());

        unset($this->conversation, $this->history, $this->sessions);
    }

    /**
     * Channel events land here as browser events (see app.js).
     */
    #[On('chat.message.received')]
    public function refreshThread(int $chatSessionId = 0): void
    {
        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->history);
        } else {
            unset($this->sessions);
        }
    }

    #[On('chat.read.updated')]
    public function refreshRead(int $chatSessionId = 0): void
    {
        unset($this->sessions);

        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->history);
        }
    }

    #[On('chat.session.closed')]
    public function refreshClosed(int $chatSessionId = 0): void
    {
        unset($this->sessions);

        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->conversation, $this->history);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.admin-chat-panel');
    }
}
