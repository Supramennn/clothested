<div class="space-y-6">
    <x-admin-nav />

    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="eyebrow">Store operations</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Dashboard</h1>
            <p class="mt-2 text-sm text-stone-600">Paid sales today and orders that still need action.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn-outline min-h-11">Open order manager</a>
    </header>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="card p-5">
            <p class="text-sm font-medium text-stone-600">Paid sales today</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-stone-900">
                {{ \App\Support\Price::format($this->paidSalesToday) }}
            </p>
            <p class="mt-1 text-sm text-stone-600">Midtrans payments settled today.</p>
        </section>
        <section class="card p-5">
            <p class="text-sm font-medium text-stone-600">Orders awaiting action</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-stone-900">{{ $this->openOrderCount }}</p>
            <p class="mt-1 text-sm text-stone-600">Pending, processing, shipped, or ready for pickup.</p>
        </section>
    </div>

    <section class="space-y-3">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-display text-xl font-semibold text-stone-900">Order queue</h2>
            <span class="text-sm text-stone-600">{{ $this->openOrderCount }} open</span>
        </div>

        <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">
            Loading current orders…
        </p>

        @if ($this->openOrders->isEmpty())
            <div class="card px-5 py-10 text-center">
                <h3 class="font-display text-lg font-semibold text-stone-900">No orders need action</h3>
                <p class="mt-2 text-sm text-stone-600">New and in-progress orders will appear here.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->openOrders as $order)
                    <a href="{{ route('admin.orders.show', $order->order_code) }}"
                       wire:key="queue-order-{{ $order->id }}"
                       class="card flex min-h-16 flex-wrap items-center justify-between gap-x-6 gap-y-2 p-4 transition-colors hover:border-stone-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-900">{{ $order->order_code }}</p>
                            <p class="mt-1 text-sm text-stone-600">
                                {{ $order->user->name }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'item' : 'items' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-status-badge :status="$order->status" />
                            <span class="text-sm font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($order->total) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
