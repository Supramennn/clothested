<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Events\ChatRead;
use App\Events\ChatSessionClosed;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Section 5.9: the chat keeps one open session per customer, pure text
 * messages, a claiming admin, and the read state of both sides.
 */
class ChatService
{
    /**
     * FR-58: the customer's open session is reused; a second one cannot open
     * while it still lives. Opening from an order page attaches that order to
     * a session without context (the return history of decision 7.3).
     */
    public function openSession(User $customer, ?Order $order = null): ChatSession
    {
        $open = ChatSession::query()
            ->where('user_id', $customer->id)
            ->where('status', 'open')
            ->first();

        if ($open !== null) {
            if ($order !== null && $open->order_id === null) {
                $open->update(['order_id' => $order->id]);
            }

            return $open->refresh();
        }

        $session = ChatSession::query()->create([
            'user_id' => $customer->id,
            'order_id' => $order?->id,
        ]);

        return $session->refresh();
    }

    /**
     * FR-59: messages are plain text, at most 2000 characters. FR-61: the
     * first admin message claims the session; the row lock keeps two admins
     * from claiming it in the same instant. FR-63: closed sessions refuse
     * anything further.
     */
    public function sendMessage(ChatSession $session, string $senderRole, string $body, ?User $sender = null): ChatMessage
    {
        if (! in_array($senderRole, ['customer', 'admin'], true)) {
            throw ValidationException::withMessages([
                'body' => 'Unknown message sender.',
            ]);
        }

        $body = trim(str_replace(["\r\n", "\r"], "\n", $body));

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Write some text before sending.',
            ]);
        }

        if (mb_strlen($body) > 2000) {
            throw ValidationException::withMessages([
                'body' => 'Keep the message to 2000 characters or fewer.',
            ]);
        }

        $message = DB::transaction(function () use ($session, $senderRole, $body, $sender): ChatMessage {
            $locked = ChatSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status === 'closed') {
                throw ValidationException::withMessages([
                    'session' => 'This conversation is closed. Start a new one to keep chatting.',
                ]);
            }

            if ($senderRole === 'admin' && $locked->admin_id === null) {
                $locked->update(['admin_id' => $sender?->id]);
            }

            return $locked->messages()->create([
                'sender_role' => $senderRole,
                'body' => $body,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        });

        $this->broadcastSafely(new ChatMessageSent($message));

        return $message;
    }

    /**
     * FR-62: reading marks only the lines from the other side; the ChatRead
     * event carries the fresh counters for both screens.
     */
    public function markRead(ChatSession $session, User $reader): void
    {
        $readerRole = $session->user_id === $reader->id ? 'customer' : 'admin';

        $locked = DB::transaction(function () use ($session, $readerRole): ChatSession {
            $locked = ChatSession::query()->lockForUpdate()->findOrFail($session->id);

            $locked->messages()
                ->where('sender_role', '!=', $readerRole)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return $locked->refresh();
        });

        $this->broadcastSafely(new ChatRead($locked->id, $this->unreadCounts($locked)));
    }

    /**
     * FR-63: only admins close a session; closing an already closed session
     * changes nothing and stays quiet. The old history rows remain stored.
     */
    public function close(ChatSession $session, User $admin): void
    {
        if (! in_array($admin->role, ['admin', 'super_admin'], true)) {
            throw ValidationException::withMessages([
                'session' => 'Only the store team can close a conversation.',
            ]);
        }

        $closed = DB::transaction(function () use ($session): ?ChatSession {
            $locked = ChatSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status === 'open') {
                $locked->update(['status' => 'closed']);

                return $locked->refresh();
            }

            return null;
        });

        if ($closed !== null) {
            $this->broadcastSafely(new ChatSessionClosed($closed->id));
        }
    }

    /**
     * FR-59: the visible history of a session is its last 50 messages,
     * oldest first.
     *
     * @return Collection<int, ChatMessage>
     */
    public function lastMessages(ChatSession $session): Collection
    {
        return $session->messages()
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Unread lines per side of one session.
     *
     * @return array{customer: int, admin: int}
     */
    public function unreadCounts(ChatSession $session): array
    {
        $counts = $session->messages()
            ->selectRaw('sender_role, COUNT(*) as total')
            ->where('is_read', false)
            ->groupBy('sender_role')
            ->pluck('total', 'sender_role');

        return [
            'customer' => (int) ($counts['customer'] ?? 0),
            'admin' => (int) ($counts['admin'] ?? 0),
        ];
    }

    /**
     * FR-62: the header badge counts every unread admin line across all the
     * customer's sessions.
     */
    public function unreadForCustomer(User $customer): int
    {
        return ChatMessage::query()
            ->whereIn('chat_session_id', ChatSession::query()->where('user_id', $customer->id)->select('id'))
            ->where('sender_role', 'admin')
            ->where('is_read', false)
            ->count();
    }

    /**
     * FR-61: the panel list of open sessions with the customer, the unread
     * count, and the newest line. Unread for the admin side means customer
     * messages nobody read yet.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openSessionOverview(): Collection
    {
        $sessions = ChatSession::query()
            ->where('status', 'open')
            ->with(['user', 'order'])
            ->get();

        $unread = ChatMessage::query()
            ->whereIn('chat_session_id', $sessions->pluck('id'))
            ->where('sender_role', 'customer')
            ->where('is_read', false)
            ->selectRaw('chat_session_id, COUNT(*) as total')
            ->groupBy('chat_session_id')
            ->pluck('total', 'chat_session_id');

        $latestIds = ChatMessage::query()
            ->whereIn('chat_session_id', $sessions->pluck('id'))
            ->selectRaw('chat_session_id, MAX(id) as last_message_id')
            ->groupBy('chat_session_id')
            ->pluck('last_message_id', 'chat_session_id');

        $previews = ChatMessage::query()
            ->whereIn('id', $latestIds->values())
            ->get()
            ->mapWithKeys(fn (ChatMessage $message): array => [$message->chat_session_id => $message]);

        return $sessions
            ->map(fn (ChatSession $session): array => [
                'id' => $session->id,
                'customer_name' => $session->user?->name ?? 'Customer',
                'unread' => (int) ($unread[$session->id] ?? 0),
                'preview' => $previews[$session->id]?->body ?? '',
                'sent_at' => $previews[$session->id]?->sent_at,
                'order_code' => $session->order?->order_code,
            ])
            ->sortByDesc(fn (array $session): int => $session['sent_at']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * A failed Reverb push must not eat a stored message; the next reload
     * still shows the line.
     */
    private function broadcastSafely(ChatMessageSent|ChatRead|ChatSessionClosed $event): void
    {
        try {
            Event::dispatch($event);
        } catch (BroadcastException|ConnectionException $exception) {
            Log::warning('The chat broadcast could not reach Reverb.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
