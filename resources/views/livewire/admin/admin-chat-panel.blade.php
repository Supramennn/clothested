<div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="eyebrow">Store team</p>
            <h1 class="type-display-lg mt-1 text-black">Chat panel</h1>
            <p class="mt-1 text-sm text-zinc-500">Open sessions on the left; the first reply claims the customer.</p>
        </div>
    </header>

    <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="card overflow-hidden self-start" wire:key="chat-sessions">
            <p class="eyebrow px-5 pt-5">Open sessions</p>
            <ul class="mt-2 divide-y divide-zinc-100 pb-2">
                @forelse ($this->sessions as $row)
                    <li wire:key="chat-session-{{ $row['id'] }}">
                        <button type="button" wire:click="selectSession({{ $row['id'] }})"
                                class="block w-full px-5 py-4 text-left transition-colors hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black {{ $sessionId === $row['id'] ? 'bg-zinc-50' : '' }}">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate font-medium text-zinc-900">{{ $row['customer_name'] }}</span>
                                @if ($row['unread'] > 0)
                                    <span class="shrink-0 rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-[550] text-white">{{ $row['unread'] }}</span>
                                @endif
                            </span>
                            <span class="mt-1 block truncate text-xs text-zinc-500">{{ $row['preview'] !== '' ? $row['preview'] : 'No messages yet.' }}</span>
                            @if ($row['order_code'])
                                <span class="mt-1 block text-[11px] text-zinc-500">Order {{ $row['order_code'] }}</span>
                            @endif
                        </button>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-zinc-500" wire:key="chat-session-empty">No open sessions right now.</li>
                @endforelse
            </ul>
        </aside>

        @if ($this->conversation === null)
            <section class="card flex min-h-[420px] items-center justify-center p-10 text-center text-sm text-zinc-500"
                     wire:key="chat-thread-empty">
                Choose a session on the left to read and reply.
            </section>
        @else
            <section class="card flex min-h-[520px] flex-col overflow-hidden" wire:key="chat-thread-{{ $sessionId }}">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4">
                    <div class="min-w-0">
                        <p class="truncate type-heading-md text-black">{{ $this->conversation->user?->name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ $this->conversation->status === 'open' ? 'Open' : 'Closed' }}
                            @if ($this->conversation->order)
                                · <a href="{{ route('admin.orders.show', $this->conversation->order->order_code) }}"
                                    class="underline decoration-zinc-300 underline-offset-2 transition-colors hover:text-black">
                                    Order {{ $this->conversation->order->order_code }}
                                </a>
                            @endif
                        </p>
                    </div>

                    @if ($this->conversation->status === 'open')
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="markRead" class="btn-outline px-5! py-2! text-sm min-h-11!">Mark read</button>
                            <button type="button" wire:click="close"
                                    wire:confirm="Close this session? The customer can start a new one."
                                    class="btn-outline px-5! py-2! text-sm min-h-11!">Close</button>
                        </div>
                    @endif
                </header>

                <ul class="grow space-y-3 overflow-y-auto px-4 py-5 sm:px-5" wire:key="chat-thread-history-{{ $this->conversation->id }}">
                    @forelse ($this->history as $line)
                        <li class="flex {{ $line->sender_role === 'admin' ? 'justify-end' : 'justify-start' }}"
                            wire:key="chat-line-{{ $line->id }}">
                            <div class="max-w-[85%] rounded-lg px-3.5 py-2.5 text-sm leading-relaxed
                                        {{ $line->sender_role === 'admin'
                                            ? 'bg-black text-white'
                                            : 'border border-zinc-200 bg-white text-zinc-700' }}">
                                <p class="whitespace-pre-wrap">{{ $line->body }}</p>
                                <p class="mt-1 text-[11px] {{ $line->sender_role === 'admin' ? 'text-zinc-400' : 'text-zinc-500' }}">
                                    {{ $line->sent_at?->format('d M, H:i') }}
                                    @if ($line->sender_role === 'admin')
                                        · {{ $line->is_read ? 'seen' : 'not seen yet' }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="py-10 text-center text-sm text-zinc-500" wire:key="chat-thread-blank">
                            No messages yet, send the first reply.
                        </li>
                    @endforelse
                </ul>

                <form wire:submit="send" class="space-y-3 border-t border-zinc-200 p-5" wire:key="chat-panel-composer">
                    @if ($this->conversation->status === 'open')
                        <label for="admin-draft" class="field-label">Reply</label>
                        <textarea id="admin-draft" rows="3" maxlength="2000" wire:model="draft"
                                  placeholder="Type a reply" class="input resize-none"></textarea>
                        @error('draft') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-zinc-500">Plain text only, attachments are not supported.</p>
                            <button type="submit" class="btn-primary px-5! py-2! text-sm min-h-11!">Send reply</button>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">
                            This session is closed. The customer starts a new one to continue the conversation.
                        </p>
                    @endif
                </form>
            </section>
        @endif
    </div>
</div>
