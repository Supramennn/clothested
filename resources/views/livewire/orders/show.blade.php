<div class="space-y-8">
    <nav class="text-sm text-zinc-500" aria-label="Breadcrumb">
        <a href="{{ route('orders.index') }}" class="transition-colors hover:text-black hover:underline">Orders</a>
        <span class="mx-1.5 text-zinc-300" aria-hidden="true">/</span>
        <span class="text-black">{{ $order->order_code }}</span>
    </nav>

    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="eyebrow">Order</p>
            <h1 class="type-display-lg mt-1 text-black">{{ $order->order_code }}</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Placed {{ $order->placed_at?->format('d M Y, H:i') }}
                · {{ $order->shipping_method === 'pickup' ? 'In-store pickup' : 'Courier delivery' }}
            </p>
        </div>

        <x-status-badge :status="$order->status" class="mt-1" />
    </header>

    @if ($notice)
        <p class="alert alert-warn" wire:key="order-notice">{{ $notice }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <section class="card overflow-hidden" wire:key="order-items">
            <ul class="divide-y divide-zinc-100">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 p-4" wire:key="order-item-{{ $item->id }}">
                        <img src="{{ $item->variant?->product?->images->first()?->url() }}"
                             alt="{{ $item->product_name }}"
                             class="h-24 w-20 shrink-0 rounded-lg border border-zinc-200 object-cover">

                        <div class="min-w-0 grow">
                            <p class="mt-0.5 font-medium text-black">{{ $item->product_name }}</p>
                            <p class="text-sm text-zinc-500">{{ $item->variant_label }}</p>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $item->quantity }} × {{ \App\Support\Price::format($item->unit_price) }}
                            </p>
                        </div>

                        <span class="shrink-0 font-[550] tabular-nums text-black">
                            {{ \App\Support\Price::format($item->line_total) }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <dl class="space-y-2 border-t border-zinc-200 bg-zinc-50/60 px-4 py-4 text-sm text-zinc-600">
                <div class="flex items-baseline justify-between">
                    <dt>Subtotal</dt>
                    <dd class="font-medium tabular-nums text-black">{{ \App\Support\Price::format($order->subtotal) }}</dd>
                </div>
                <div class="flex items-baseline justify-between">
                    <dt>Shipping ({{ $order->shipping_method === 'pickup' ? 'pickup' : 'flat courier' }})</dt>
                    <dd class="font-medium tabular-nums text-black">{{ \App\Support\Price::format($order->shipping_fee) }}</dd>
                </div>
                <div class="flex items-baseline justify-between border-t border-zinc-200 pt-2">
                    <dt class="font-medium text-black">Total</dt>
                    <dd class="text-lg font-[550] tabular-nums text-black">{{ \App\Support\Price::format($order->total) }}</dd>
                </div>
            </dl>
        </section>

        <div class="space-y-6">
            <section class="card p-5" wire:key="order-delivery">
                <h2 class="type-heading-md text-black">
                    {{ $order->shipping_method === 'pickup' ? 'In-store pickup' : 'Delivery address' }}
                </h2>

                @if ($order->shipping_method === 'pickup')
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">
                        No address needed. The store marks the order ready for pickup once it is prepared.
                    </p>
                @elseif ($order->shippingAddress)
                    <address class="mt-2 text-sm leading-relaxed text-zinc-600 not-italic">
                        <span class="block font-medium text-black">{{ $order->shippingAddress->recipient_name }}</span>
                        <span class="block">{{ $order->shippingAddress->phone }}</span>
                        <span class="block">{{ $order->shippingAddress->address_line }}</span>
                        <span class="block">{{ $order->shippingAddress->city }} {{ $order->shippingAddress->postal_code }}</span>
                    </address>
                    @if ($order->shippingAddress->waybill_number)
                        <p class="mt-3 text-sm text-zinc-600">
                            Waybill: <span class="font-medium text-black">{{ $order->shippingAddress->waybill_number }}</span>
                        </p>
                    @endif
                @endif

                @if ($order->note)
                    <div class="mt-4 border-t border-zinc-100 pt-4">
                        <p class="eyebrow">Note</p>
                        <p class="mt-1 text-sm leading-relaxed text-zinc-600">{{ $order->note }}</p>
                    </div>
                @endif
            </section>

            @if ($order->payment)
                <section class="card p-5" wire:key="order-payment">
                    <h2 class="type-heading-md text-black">Payment</h2>
                    <p class="mt-2 text-sm text-zinc-600">
                        Status: <span class="font-medium text-black">{{ ucfirst($order->payment->transaction_status) }}</span>
                    </p>
                    @if ($order->payment->expiry_time)
                        <p class="mt-1 text-sm text-zinc-500">
                            Pay before {{ $order->payment->expiry_time->format('d M Y, H:i') }}.
                        </p>
                    @endif
                    @if ($order->payment->transaction_status === 'pending')
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">
                            Complete payment in the Midtrans Snap window. If you have already paid, check the latest status here.
                        </p>
                        <button type="button" wire:click="checkPaymentStatus" class="btn-outline mt-4">
                            Check payment status
                        </button>
                    @endif
                </section>
            @elseif ($order->isCancellable())
                <section class="card p-5" wire:key="order-payment">
                    <h2 class="type-heading-md text-black">Payment</h2>
                    <p class="mt-2 leading-relaxed text-sm text-zinc-500">
                        Start the secure Midtrans Snap payment for this pending order.
                    </p>
                    <button type="button" wire:click="startPayment" class="btn-primary mt-4">
                        Continue to Midtrans Snap
                    </button>
                </section>
            @endif

            @if ($order->status === 'shipped' || $order->status === 'ready_for_pickup')
                <section class="card p-5" wire:key="order-receipt">
                    <h2 class="type-heading-md text-black">Confirm receipt</h2>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                        {{ $order->shipping_method === 'pickup'
                            ? 'Confirm after you collect the order from the store.'
                            : 'Confirm after the courier delivers the order.' }}
                    </p>
                    <button type="button" wire:click="confirmReceipt" class="btn-primary mt-4 min-h-11">
                        Confirm receipt
                    </button>
                </section>
            @endif

            @if ($order->isCancellable())
                <section class="card p-5" wire:key="order-cancel">
                    <h2 class="type-heading-md text-black">Cancel this order</h2>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">
                        Cancellation is possible while the payment is still pending. The reserved stock returns to the catalog immediately.
                    </p>
                    <button type="button" wire:click="cancel"
                            wire:confirm="Cancel this order? The reserved stock returns to the catalog."
                            class="mt-4 inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-[550] text-red-600 transition-colors hover:border-red-300 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        Cancel order
                    </button>
                </section>
            @endif

            <section class="card p-5" wire:key="order-chat">
                <h2 class="type-heading-md text-black">Help with this order</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                    Ask the team through the chat; opening it from here links this order as the conversation context.
                </p>
                <a href="{{ route('chat', ['order' => $order->order_code]) }}"
                   class="btn-outline mt-4 min-h-11 px-4">
                    Open the chat
                </a>
            </section>
        </div>
    </div>
</div>
