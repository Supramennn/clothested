<div class="mx-auto max-w-3xl space-y-6"
     x-data
     x-init="$el.dispatchEvent(new CustomEvent('chat:subscribe', {detail: {chatSessionId: @js($sessionId)}}))">

    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="eyebrow">Support</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Chat with ClothesTed</h1>
            <p class="mt-1 text-sm text-stone-500">One conversation at a time. Replies arrive live — nothing to reload.</p>
        </div>

        @if ($session)
            @if ($session->status === 'open')
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">Open</span>
            @else
                <span class="rounded-full border border-stone-300 bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600">Closed</span>
            @endif
        @endif
    </header>

    @if ($session?->order)
        <p class="flex flex-wrap items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm text-stone-600" wire:key="chat-order-context">
            <span class="font-medium text-stone-900">Context order</span>
            <a href="{{ route('orders.show', $session->order->order_code) }}"
               class="font-semibold text-stone-900 underline decoration-stone-300 underline-offset-2 transition-colors hover:text-stone-950">
                {{ $session->order->order_code }}
            </a>
        </p>
    @endif

    <section class="card overflow-hidden" wire:key="chat-thread">
        @if ($session === null)
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <p class="font-display text-xl font-semibold text-stone-900">Ready when you are</p>
                <p class="mt-2 max-w-sm text-sm text-stone-500">
                    Open a conversation and the store team answers right in this window.
                </p>
                <button type="button" wire:click="startNew" class="btn-primary mt-6">Start a conversation</button>
            </div>
        @else
            <ul class="max-h-96 space-y-3 overflow-y-auto px-5 py-5" wire:key="chat-thread-{{ $session->id }}">
                @forelse ($this->history as $line)
                    <li class="flex {{ $line->sender_role === 'customer' ? 'justify-end' : 'justify-start' }}"
                        wire:key="chat-line-{{ $line->id }}">
                        <div class="max-w-[85%] rounded-lg px-3.5 py-2.5 text-sm leading-relaxed
                                    {{ $line->sender_role === 'customer'
                                        ? 'bg-stone-900 text-stone-50'
                                        : 'border border-stone-200 bg-white text-stone-700' }}">
                            <p class="whitespace-pre-wrap">{{ $line->body }}</p>
                            <p class="mt-1 text-[11px] text-stone-400">
                                {{ $line->sent_at?->format('d M, H:i') }} · {{ $line->sender_role === 'admin' ? 'store team' : 'you' }}
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-stone-500" wire:key="chat-empty">
                        No messages yet. Ask about a product, an order, or anything else.
                    </li>
                @endforelse
            </ul>
        @endif
    </section>

    @if ($session?->status === 'closed')
        <p class="alert alert-warn">
            This conversation is closed. The history stays above, or start a fresh topic below.
        </p>
        <div class="flex justify-center">
            <button type="button" wire:click="startNew" class="btn-primary">Start a new conversation</button>
        </div>
    @elseif ($session)
        <form wire:submit="send" class="card space-y-3 p-5" wire:key="chat-composer">
            <label for="chat-draft" class="field-label">Message</label>
            <textarea id="chat-draft" rows="3" maxlength="2000" wire:model="draft"
                      placeholder="Type your message" class="input resize-none"></textarea>
            @error('draft') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-stone-400">Plain text only — attachments are not supported.</p>
                <button type="submit" class="btn-primary px-5">Send</button>
            </div>
        </form>
    @endif
</div>
