<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Section 5.9: one open conversation for the signed-in customer, with the
 * optional order context and a fresh start after the old session closed.
 */
#[Layout('layouts.app')]
#[Title('Chat — ClothesTed')]
class CustomerChat extends Component
{
    #[Url]
    public string $order = '';

    public ?int $sessionId = null;

    public string $draft = '';

    public function mount(ChatService $chat): void
    {
        $last = ChatSession::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->first();

        if ($last === null) {
            // FR-58: the first visit opens the one session.
            $this->sessionId = $chat->openSession(Auth::user())->id;

            return;
        }

        if ($last->status === 'open') {
            // Reuse the open session; an order-page visit attaches its order.
            $order = $this->order === '' ? null : Order::query()
                ->where('user_id', Auth::id())
                ->where('order_code', $this->order)
                ->first();

            $this->sessionId = $chat->openSession(Auth::user(), $order)->id;

            return;
        }

        // FR-63: the last one closed; a fresh session starts only by choice.
        $this->sessionId = null;
    }

    /**
     * FR-63: a closed session stays visible for its history until the
     * customer starts a fresh one.
     */
    #[Computed]
    public function session(): ?ChatSession
    {
        return $this->sessionId === null
            ? null
            : ChatSession::query()->with('order')->find($this->sessionId);
    }

    /**
     * FR-59: the visible thread holds the last 50 messages, oldest first.
     *
     * @return Collection<int, ChatMessage>
     */
    #[Computed]
    public function history(): Collection
    {
        $session = $this->session;

        return $session === null
            ? collect()
            : app(ChatService::class)->lastMessages($session);
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
     * The private channel pushes land here as browser events (see app.js).
     */
    #[On('chat.message.received')]
    public function refreshThread(int $chatSessionId = 0): void
    {
        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->history);
        }
    }

    #[On('chat.read.updated')]
    public function refreshRead(int $chatSessionId = 0): void
    {
        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->history);
        }
    }

    #[On('chat.session.closed')]
    public function refreshClosed(int $chatSessionId = 0): void
    {
        if ((int) $chatSessionId === (int) $this->sessionId) {
            unset($this->session);
        }
    }

    /**
     * FR-63: after the session closes the customer starts a fresh one; the
     * old history stays stored (its rows never disappear).
     */
    public function startNew(ChatService $chat): void
    {
        if ($this->session?->status === 'open') {
            return;
        }

        $this->reset('draft');
        $this->resetErrorBag();
        $this->sessionId = $chat->openSession(Auth::user())->id;

        $this->dispatch('chat.subscribe', chatSessionId: $this->sessionId);
    }

    /**
     * FR-59: send the current draft as one customer message.
     */
    public function send(ChatService $chat): void
    {
        $this->validate();

        $chat->sendMessage($this->session, 'customer', $this->draft, Auth::user());

        $this->reset('draft');
        $this->resetErrorBag();
        unset($this->history);
    }

    public function render(): View
    {
        // session is a computed property; pass it explicitly so the view can
        // render the closed/no-session states safely.
        return view('livewire.customer-chat', ['session' => $this->session]);
    }
}
