<?php

use App\Events\ChatMessageSent;
use App\Events\ChatRead;
use App\Events\ChatSessionClosed;
use App\Livewire\Admin\AdminChatPanel;
use App\Livewire\ChatButton;
use App\Livewire\CustomerChat;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Order;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * Opens one session for the customer through the service.
 */
function chatSessionFor(User $customer, ?Order $order = null): ChatSession
{
    return app(ChatService::class)->openSession($customer, $order);
}

function chatAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('an open chat session is reused instead of a second one (FR-58)', function () {
    $customer = customer();

    $first = chatSessionFor($customer);
    $second = chatSessionFor($customer);

    expect($first->id)->toBe($second->id)
        ->and(ChatSession::query()->count())->toBe(1);
});

test('opening the chat from an order page links the order (FR-58)', function () {
    $customer = customer();
    $order = Order::factory()->for($customer)->create();

    $session = chatSessionFor($customer, $order);

    expect($session->order_id)->toBe($order->id)
        // A second visit without an order context keeps the linked order.
        ->and(chatSessionFor($customer)->refresh()->order_id)->toBe($order->id);
});

test('an open session without context picks up the order link later (FR-58)', function () {
    $customer = customer();
    $order = Order::factory()->for($customer)->create();

    $session = chatSessionFor($customer);

    expect($session->order_id)->toBeNull();

    chatSessionFor($customer, $order);

    expect($session->refresh()->order_id)->toBe($order->id)
        ->and(ChatSession::query()->count())->toBe(1);
});

test('messages are bounded plain text (FR-59, FR-64)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    expect(fn () => $chat->sendMessage($session, 'customer', '   ', $customer))
        ->toThrow(ValidationException::class)
        ->and(fn () => $chat->sendMessage($session, 'customer', str_repeat('a', 2001), $customer))
        ->toThrow(ValidationException::class)
        ->and(fn () => $chat->sendMessage($session, 'visitor', 'no such role', $customer))
        ->toThrow(ValidationException::class);

    $message = $chat->sendMessage($session, 'customer', '  Hi, is the Flow Dress true to size?  ', $customer);

    expect($message->body)->toBe('Hi, is the Flow Dress true to size?')
        ->and($message->is_read)->toBeFalse();
});

test('the visible history is the last 50 messages (FR-59)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    for ($i = 1; $i <= 55; $i++) {
        $chat->sendMessage($session, 'customer', "Line {$i}.", $customer);
    }

    $history = $chat->lastMessages($session);

    expect($history)->toHaveCount(50)
        ->and($history->first()->body)->toBe('Line 6.')
        ->and($history->last()->body)->toBe('Line 55.');
});

test('the first admin reply claims the session, later replies keep it (FR-61)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $firstAdmin = chatAdmin();
    $secondAdmin = User::factory()->create(['role' => 'super_admin']);
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    $chat->sendMessage($session, 'customer', 'Hello', $customer);
    $chat->sendMessage($session, 'admin', 'We got you.', $firstAdmin);
    $chat->sendMessage($session, 'admin', 'Following up again.', $secondAdmin);

    expect($session->refresh()->admin_id)->toBe($firstAdmin->id);
});

test('reading flips only the lines from the other side (FR-62)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $admin = chatAdmin();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    $customerLine = $chat->sendMessage($session, 'customer', 'Where is my parcel?', $customer)->refresh();
    $adminLine = $chat->sendMessage($session, 'admin', 'It ships today.', $admin)->refresh();

    $chat->markRead($session, $admin);

    expect($customerLine->refresh()->is_read)->toBeTrue()
        ->and($adminLine->refresh()->is_read)->toBeFalse()
        ->and($chat->unreadCounts($session))->toBe(['customer' => 0, 'admin' => 1]);

    $chat->markRead($session, $customer);

    expect($adminLine->refresh()->is_read)->toBeTrue()
        ->and($chat->unreadCounts($session))->toBe(['customer' => 0, 'admin' => 0]);

    Event::assertDispatched(fn (ChatRead $event): bool => $event->chatSessionId === $session->id);
});

test('the header badge counts unread replies for the customer (FR-62)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $admin = chatAdmin();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    $chat->sendMessage($session, 'admin', 'Quick update from the store.', $admin)->refresh();

    Livewire::actingAs($customer)->test(ChatButton::class)
        ->assertSee('1');

    $chat->markRead($session, $customer);

    Livewire::actingAs($customer)->test(ChatButton::class)
        ->assertDontSee('chat-badge');
});

test('closing a session refuses messages but keeps the history (FR-63)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $admin = chatAdmin();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    $chat->sendMessage($session, 'customer', 'Anything about returns?', $customer);
    $chat->close($session, $admin);

    expect($session->refresh()->status)->toBe('closed')
        ->and(fn () => $chat->sendMessage($session, 'customer', 'One more question.', $customer))
        ->toThrow(ValidationException::class)
        ->and(ChatMessage::query()->where('chat_session_id', $session->id)->count())->toBe(1);

    Event::assertDispatched(fn (ChatSessionClosed $event): bool => $event->chatSessionId === $session->id);

    // Closing twice changes nothing and stays quiet about it.
    $chat->close($session, $admin);

    Event::assertDispatchedTimes(ChatSessionClosed::class, 1);
});

test('a non-admin cannot close a session (FR-63)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $session = chatSessionFor($customer);

    expect(fn () => app(ChatService::class)->close($session, $customer))
        ->toThrow(ValidationException::class)
        ->and($session->refresh()->status)->toBe('open');
});

test('the customer starts a fresh session only after the old one closed (FR-63)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $admin = chatAdmin();
    $session = chatSessionFor($customer);
    $chat = app(ChatService::class);

    $chat->sendMessage($session, 'customer', 'Old topic.', $customer);
    $chat->close($session, $admin);

    $component = Livewire::actingAs($customer)->test(CustomerChat::class);

    expect($component->get('sessionId'))->toBeNull()
        ->and(ChatSession::query()->where('user_id', $customer->id)->count())->toBe(1);

    $component->call('startNew');

    $fresh = ChatSession::query()->where('user_id', $customer->id)->orderByDesc('id')->first();

    expect($component->get('sessionId'))->toBe($fresh->id)
        ->and($session->refresh()->messages()->count())->toBe(1);
});

test('messages flow while the first visit opens exactly one session (FR-58, FR-60)', function () {
    Event::fake([ChatMessageSent::class, ChatRead::class, ChatSessionClosed::class]);

    $customer = customer();
    $admin = chatAdmin();

    $visit = Livewire::actingAs($customer)->test(CustomerChat::class)
        ->set('draft', 'Hello there!')
        ->call('send');

    $session = ChatSession::query()->where('user_id', $customer->id)->sole();
    $customerLine = $session->messages()->sole();

    expect($visit->get('sessionId'))->toBe($session->id)
        ->and($customerLine->sender_role)->toBe('customer')
        ->and($customerLine->body)->toBe('Hello there!');

    Event::assertDispatched(fn (ChatMessageSent $event): bool => $event->message->id === $customerLine->id);

    $panel = Livewire::actingAs($admin)->test(AdminChatPanel::class)
        ->assertSee('Hello there!')
        ->call('selectSession', $session->id)
        ->assertSee($customer->name)
        ->set('draft', 'Welcome, we are glad to help.')
        ->call('send');

    expect($session->refresh()->admin_id)->toBe($admin->id)
        ->and($session->messages()->where('sender_role', 'admin')->count())->toBe(1)
        ->and($panel->get('sessionId'))->toBe($session->id);
});

test('the chat channel authorizes only the participants (FR-60)', function () {
    $customer = customer();
    $stranger = customer();
    $admin = chatAdmin();
    $session = chatSessionFor($customer);

    $this->actingAs($customer)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.'.$session->id, 'socket_id' => '1234.5678'])
        ->assertOk();

    $this->actingAs($stranger)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.'.$session->id, 'socket_id' => '1234.5678'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.'.$session->id, 'socket_id' => '1234.5678'])
        ->assertOk();

    $claimer = chatAdmin();
    $session->update(['admin_id' => $claimer->id]);

    $this->actingAs($admin)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.'.$session->id, 'socket_id' => '1234.5678'])
        ->assertForbidden();

    $this->actingAs($claimer)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.'.$session->id, 'socket_id' => '1234.5678'])
        ->assertOk();
});
