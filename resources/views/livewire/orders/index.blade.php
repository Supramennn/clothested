<div class="space-y-6">
    <header>
        <h1 class="type-display-lg text-black">Orders</h1>
        <p class="mt-1 text-sm text-zinc-500">Every order placed with this account, newest first.</p>
    </header>

    @if ($this->orders->isEmpty())
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center" wire:key="orders-empty">
            <p class="type-heading-lg text-black">No orders yet</p>
            <p class="mt-2 max-w-sm text-sm text-zinc-500">
                An order appears here right after checkout reserves its stock.
            </p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Browse the catalog</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->orders as $order)
                <article class="card p-5" wire:key="order-{{ $order->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="eyebrow">Order</p>
                            <a href="{{ route('orders.show', $order->order_code) }}"
                               class="type-heading-md text-black transition-colors hover:underline">
                                {{ $order->order_code }}
                            </a>
                            <p class="mt-1 text-sm text-zinc-500">
                                Placed {{ $order->placed_at?->format('d M Y, H:i') }}
                                · {{ $order->items_count }} {{ $order->items_count === 1 ? 'item' : 'items' }}
                                · {{ $order->shipping_method === 'pickup' ? 'Pickup' : 'Courier' }}
                            </p>
                        </div>

                        <x-status-badge :status="$order->status" />
                    </div>

                    <div class="mt-4 flex flex-wrap items-baseline justify-between gap-3 border-t border-zinc-100 pt-4">
                        <p class="text-sm text-zinc-500">
                            Total
                            <span class="ml-1 font-[550] tabular-nums text-black">{{ \App\Support\Price::format($order->total) }}</span>
                        </p>

                        <a href="{{ route('orders.show', $order->order_code) }}" class="btn-outline px-5! py-2! text-sm min-h-11!">
                            View details
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="flex justify-center">
            {{ $this->orders->links() }}
        </div>
    @endif
</div>
