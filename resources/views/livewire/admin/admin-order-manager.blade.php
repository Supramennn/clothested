<div class="space-y-6">
    <x-admin-nav />

    @if ($notice)
        <p class="alert alert-success" role="status">{{ $notice }}</p>
    @endif
    @error('order')
        <p class="alert alert-danger" role="alert">{{ $message }}</p>
    @enderror

    @if ($this->selectedOrder)
        @php($order = $this->selectedOrder)
        <nav class="text-sm text-stone-600" aria-label="Breadcrumb">
            <a href="{{ route('admin.orders.index') }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Order manager</a>
            <span class="mx-1.5" aria-hidden="true">/</span>
            <span class="text-stone-900">{{ $order->order_code }}</span>
        </nav>

        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="eyebrow">Order details</p>
                <h1 class="mt-1 break-all font-display text-3xl font-semibold tracking-tight text-stone-900">{{ $order->order_code }}</h1>
                <p class="mt-2 text-sm text-stone-600">
                    {{ $order->user->name }} · {{ $order->user->email }} · Placed {{ $order->placed_at?->format('d M Y, H:i') }}
                </p>
            </div>
            <x-status-badge :status="$order->status" />
        </header>

        <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Saving order changes…</p>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.8fr)]">
            <section class="card overflow-hidden">
                <h2 class="border-b border-stone-200 px-5 py-4 font-display text-xl font-semibold text-stone-900">Items</h2>
                <ul class="divide-y divide-stone-100">
                    @foreach ($order->items as $item)
                        <li wire:key="admin-order-item-{{ $item->id }}" class="flex flex-wrap items-center justify-between gap-3 p-4 sm:px-5">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900">{{ $item->product_name }}</p>
                                <p class="mt-1 text-sm text-stone-600">{{ $item->variant_label }} · {{ $item->quantity }} × {{ \App\Support\Price::format($item->unit_price) }}</p>
                            </div>
                            <p class="font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($item->line_total) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-stone-200 bg-stone-50 px-5 py-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-stone-600">Subtotal</dt><dd class="font-medium text-stone-900">{{ \App\Support\Price::format($order->subtotal) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-stone-600">Shipping</dt><dd class="font-medium text-stone-900">{{ \App\Support\Price::format($order->shipping_fee) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-stone-200 pt-2"><dt class="font-semibold text-stone-900">Total</dt><dd class="font-semibold text-stone-900">{{ \App\Support\Price::format($order->total) }}</dd></div>
                </dl>
            </section>

            <div class="space-y-5">
                <section class="card p-5">
                    <h2 class="font-display text-lg font-semibold text-stone-900">
                        {{ $order->shipping_method === 'pickup' ? 'Pickup' : 'Courier delivery' }}
                    </h2>
                    @if ($order->shippingAddress)
                        <address class="mt-3 text-sm leading-relaxed text-stone-600 not-italic">
                            <span class="block font-medium text-stone-900">{{ $order->shippingAddress->recipient_name }}</span>
                            <span class="block">{{ $order->shippingAddress->phone }}</span>
                            <span class="block">{{ $order->shippingAddress->address_line }}</span>
                            <span class="block">{{ $order->shippingAddress->city }} {{ $order->shippingAddress->postal_code }}</span>
                        </address>
                        @if ($order->shippingAddress->waybill_number)
                            <p class="mt-3 break-all text-sm text-stone-600">Waybill: <span class="font-medium text-stone-900">{{ $order->shippingAddress->waybill_number }}</span></p>
                        @endif
                    @endif
                    @if ($order->note)
                        <div class="mt-4 border-t border-stone-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-600">Customer note</p>
                            <p class="mt-1 text-sm leading-relaxed text-stone-700">{{ $order->note }}</p>
                        </div>
                    @endif
                </section>

                @if ($order->payment)
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Payment</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-stone-600">Status</dt><dd class="font-medium text-stone-900">{{ ucfirst($order->payment->transaction_status) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-stone-600">Payment type</dt><dd class="font-medium text-stone-900">{{ $order->payment->payment_type ?? 'Not recorded' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-stone-600">Paid at</dt><dd class="font-medium text-stone-900">{{ $order->payment->paid_at?->format('d M Y, H:i') ?? 'Not paid' }}</dd></div>
                        </dl>
                        @if (in_array($order->payment->transaction_status, ['expire', 'cancel', 'deny'], true))
                            <div class="alert alert-warn mt-4">
                                Payment did not settle. Check the stored provider details before contacting the customer.
                                @php($payload = $order->payment->raw_payload ?? [])
                                @foreach (['transaction_status', 'status_code', 'status_message', 'fraud_status'] as $key)
                                    @if (isset($payload[$key]))
                                        <p class="mt-1 text-sm"><span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span> {{ $payload[$key] }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </section>
                @elseif ($order->status === 'pending')
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Payment</h2>
                        <p class="mt-2 text-sm text-stone-600">No payment session is recorded. The customer can retry payment from the order page.</p>
                    </section>
                @endif

                @if ($order->status === 'processing' && $order->shipping_method === 'pickup')
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Prepare pickup</h2>
                        <p class="mt-2 text-sm text-stone-600">Mark this order ready when the customer can collect it.</p>
                        <button type="button" wire:click="markReadyForPickup" class="btn-primary mt-4 min-h-11" wire:loading.attr="disabled">
                            Mark ready for pickup
                        </button>
                    </section>
                @endif

                @if ($order->status === 'processing' && $order->shipping_method === 'courier')
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Ship order</h2>
                        <form wire:submit="markShipped" class="mt-3 space-y-3">
                            <div>
                                <label for="waybill-number" class="field-label">Waybill number</label>
                                <input id="waybill-number" type="text" wire:model="waybillNumber" class="input mt-1 min-h-11">
                                @error('waybillNumber') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-primary min-h-11" wire:loading.attr="disabled">Save waybill and mark shipped</button>
                        </form>
                    </section>
                @endif

                @if (in_array($order->status, ['shipped', 'ready_for_pickup'], true))
                    <p class="alert alert-warn">Waiting for the customer to confirm receipt before this order can be completed.</p>
                @endif
                @if ($order->status === 'shipped')
                    <p class="text-sm text-stone-600">Shipped orders cannot be cancelled from this screen.</p>
                @endif

                @if (in_array($order->status, ['pending', 'processing', 'ready_for_pickup'], true))
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Cancel order</h2>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600">
                            Stock returns to the catalog. Record the manual refund or cancellation action here; ClothesTed does not issue refunds through this screen.
                        </p>
                        <form wire:submit="cancelOrder" class="mt-4 space-y-3">
                            <div>
                                <label for="refund-note" class="field-label">Refund or cancellation note</label>
                                <textarea id="refund-note" wire:model="refundNote" rows="3" class="input mt-1"></textarea>
                                @error('refundNote') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" wire:confirm="Cancel this order and return its reserved stock?"
                                    class="btn-outline min-h-11 border-red-200 text-red-700 hover:border-red-300 hover:text-red-800"
                                    wire:loading.attr="disabled">
                                Cancel order
                            </button>
                        </form>
                    </section>
                @elseif ($order->refund_note)
                    <section class="card p-5">
                        <h2 class="font-display text-lg font-semibold text-stone-900">Cancellation note</h2>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $order->refund_note }}</p>
                    </section>
                @endif
            </div>
        </div>
    @else
        <header>
            <p class="eyebrow">Store operations</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Order manager</h1>
            <p class="mt-2 text-sm text-stone-600">Filter orders by their current fulfillment state.</p>
        </header>

        <div class="flex flex-wrap items-center gap-3">
            <label for="order-status-filter" class="field-label">Show</label>
            <select id="order-status-filter" wire:model.live="statusFilter" class="input min-h-11 w-auto">
                <option value="open">Open orders</option>
                <option value="all">All orders</option>
                @foreach (['pending', 'processing', 'shipped', 'ready_for_pickup', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>

        <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Loading orders…</p>

        @if ($this->orders->isEmpty())
            <div class="card px-5 py-12 text-center">
                <h2 class="font-display text-xl font-semibold text-stone-900">No orders in this view</h2>
                <p class="mt-2 text-sm text-stone-600">Choose another status to see matching orders.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->orders as $order)
                    <a href="{{ route('admin.orders.show', $order->order_code) }}"
                       wire:key="manager-order-{{ $order->id }}"
                       class="card flex min-h-16 flex-wrap items-center justify-between gap-x-6 gap-y-3 p-4 transition-colors hover:border-stone-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-900">{{ $order->order_code }}</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $order->user->name }} · {{ $order->placed_at?->format('d M Y, H:i') }} · {{ $order->items_count }} items</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-status-badge :status="$order->status" />
                            <span class="font-semibold tabular-nums text-stone-900">{{ \App\Support\Price::format($order->total) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="flex justify-center">{{ $this->orders->links() }}</div>
        @endif
    @endif
</div>
