<div class="space-y-6">
    <x-admin-nav />

    <header>
        <p class="eyebrow">Store operations</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Sales report</h1>
        <p class="mt-2 text-sm text-stone-600">Settled Midtrans orders only. Cancelled orders are excluded from sales.</p>
    </header>

    <form wire:submit="runReport" class="card grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="report-from" class="field-label">From</label>
            <input id="report-from" type="date" wire:model="fromDate" class="input mt-1 min-h-11">
            @error('fromDate') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="report-to" class="field-label">To</label>
            <input id="report-to" type="date" wire:model="toDate" class="input mt-1 min-h-11">
            @error('toDate') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary min-h-11" wire:loading.attr="disabled">Apply dates</button>
    </form>

    <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Updating the sales report…</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="card p-5">
            <p class="text-sm font-medium text-stone-600">Settled sales</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($this->paidSales) }}</p>
            <p class="mt-1 text-sm text-stone-600">{{ $appliedFromDate }} through {{ $appliedToDate }}</p>
        </section>
        <section class="card p-5">
            <p class="text-sm font-medium text-stone-600">Paid orders</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-stone-900">{{ $this->paidOrderCount }}</p>
            <p class="mt-1 text-sm text-stone-600">Payment status is settled.</p>
        </section>
    </div>

    @if ($this->paidOrders->isEmpty())
        <div class="card px-5 py-10 text-center">
            <h2 class="font-display text-xl font-semibold text-stone-900">No settled sales in this period</h2>
            <p class="mt-2 text-sm text-stone-600">Choose a wider date range to include more orders.</p>
        </div>
    @else
        <section class="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
            <div class="space-y-3">
                <h2 class="font-display text-xl font-semibold text-stone-900">Daily totals</h2>
                @foreach ($this->dailySales as $day)
                    <article wire:key="sales-day-{{ $day->sales_date }}" class="card flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <h3 class="font-medium text-stone-900">{{ \Illuminate\Support\Carbon::parse($day->sales_date)->format('d M Y') }}</h3>
                            <p class="mt-1 text-sm text-stone-600">{{ $day->order_count }} {{ $day->order_count === 1 ? 'order' : 'orders' }}</p>
                        </div>
                        <p class="font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($day->revenue) }}</p>
                    </article>
                @endforeach
            </div>

            <div class="space-y-3">
                <h2 class="font-display text-xl font-semibold text-stone-900">Settled orders</h2>
                @foreach ($this->paidOrders as $order)
                    <a href="{{ route('admin.orders.show', $order->order_code) }}"
                       wire:key="sales-order-{{ $order->id }}"
                       class="card flex min-h-16 flex-wrap items-center justify-between gap-x-6 gap-y-2 p-4 transition-colors hover:border-stone-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $order->order_code }}</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $order->user->name }} · {{ $order->placed_at?->format('d M Y, H:i') }}</p>
                        </div>
                        <span class="font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($order->total) }}</span>
                    </a>
                @endforeach
                <div class="flex justify-center">{{ $this->paidOrders->links() }}</div>
            </div>
        </section>
    @endif
</div>
