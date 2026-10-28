<div class="mx-auto max-w-3xl space-y-6"
     x-data
     x-init="$el.dispatchEvent(new CustomEvent('chat:subscribe', {detail: {chatSessionId: @js($sessionId)}}))">

    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="eyebrow">Support</p>
            <h1 class="type-display-lg mt-1 text-black">Chat with ClothesTed</h1>
            <p class="mt-1 text-sm text-zinc-500">One conversation at a time. Replies arrive live, nothing to reload.</p>
        </div>

        @if ($session)
            @if ($session->status === 'open')
                <span class="rounded-full border border-aloe bg-aloe px-3 py-1 text-xs font-medium text-black">Open</span>
            @else
                <span class="rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600">Closed</span>
            @endif
        @endif
    </header>

    @if ($session?->order)
        <p class="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-600" wire:key="chat-order-context">
            <span class="font-medium text-black">Context order</span>
            <a href="{{ route('orders.show', $session->order->order_code) }}"
               class="font-[550] text-black underline decoration-zinc-300 underline-offset-2 transition-colors hover:text-zinc-600">
                {{ $session->order->order_code }}
            </a>
        </p>
    @endif

    <section class="card overflow-hidden" wire:key="chat-thread">
        @if ($session === null)
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <p class="type-heading-lg text-black">Ready when you are</p>
                <p class="mt-2 max-w-sm text-sm text-zinc-500">
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
                                        ? 'bg-black text-white'
                                        : 'border border-zinc-200 bg-white text-zinc-700' }}">
                            <p class="whitespace-pre-wrap">{{ $line->body }}</p>
                            <p class="mt-1 text-[11px] {{ $line->sender_role === 'customer' ? 'text-zinc-400' : 'text-zinc-500' }}">
                                {{ $line->sent_at?->format('d M, H:i') }} · {{ $line->sender_role === 'admin' ? 'store team' : 'you' }}
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-zinc-500" wire:key="chat-empty">
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
                <p class="text-xs text-zinc-500">Plain text only, attachments are not supported.</p>
                <button type="submit" class="btn-primary px-5! py-2! text-sm min-h-11!">Send</button>
            </div>
        </form>
    @endif
</div>
