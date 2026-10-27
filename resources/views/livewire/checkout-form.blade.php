<div class="space-y-8">
    <header>
        <h1 class="font-display text-3xl font-semibold tracking-tight text-stone-900">Checkout</h1>
        <p class="mt-1 text-sm text-stone-500">Choose how to receive the order, confirm the summary, and lock the stock.</p>
    </header>

    @if ($this->cartItems->isEmpty())
        <div class="flex flex-col items-center rounded-xl border border-stone-200 bg-white px-6 py-16 text-center" wire:key="checkout-empty">
            <p class="font-display text-xl font-semibold text-stone-900">Your cart is empty</p>
            <p class="mt-2 max-w-sm text-sm text-stone-500">
                Add a piece to the cart before checking out.
            </p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Browse the catalog</a>
        </div>
    @else
        <form wire:submit="placeOrder" class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                <section class="card p-5" wire:key="method-section">
                    <h2 class="font-display text-lg font-semibold text-stone-900">Handover method</h2>
                    <p class="mt-1 text-sm text-stone-500">Pickup costs nothing. Courier uses the flat national rate.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="shippingMethod" value="pickup" class="peer sr-only">
                            <span class="block h-full rounded-lg border border-stone-300 p-4 transition-colors hover:border-stone-400
                                         peer-checked:border-stone-900 peer-checked:ring-1 peer-checked:ring-stone-900
                                         peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sky-700">
                                <span class="font-medium text-stone-900">In-store pickup</span>
                                <span class="mt-1 block text-sm text-stone-500">No address needed; the fee is Rp 0.</span>
                            </span>
                        </label>

                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="shippingMethod" value="courier" class="peer sr-only">
                            <span class="block h-full rounded-lg border border-stone-300 p-4 transition-colors hover:border-stone-400
                                         peer-checked:border-stone-900 peer-checked:ring-1 peer-checked:ring-stone-900
                                         peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sky-700">
                                <span class="font-medium text-stone-900">Courier delivery</span>
                                <span class="mt-1 block text-sm text-stone-500">
                                    Flat rate {{ $price::format(config('clothested.shipping_fee')) }}; a full address is required.
                                </span>
                            </span>
                        </label>
                    </div>

                    @error('shippingMethod')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </section>

                @if ($shippingMethod === 'courier')
                    <section class="card space-y-4 p-5" wire:key="address-section">
                        <div>
                            <h2 class="font-display text-lg font-semibold text-stone-900">Delivery address</h2>
                            <p class="mt-1 text-sm text-stone-500">Every field is required for a courier order.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="recipient-name" class="field-label mb-1">Recipient name</label>
                                <input id="recipient-name" type="text" wire:model="recipientName" class="input">
                                @error('recipientName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="checkout-phone" class="field-label mb-1">Phone</label>
                                <input id="checkout-phone" type="text" wire:model="phone" class="input">
                                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address-line" class="field-label mb-1">Address line</label>
                            <input id="address-line" type="text" wire:model="addressLine" class="input">
                            @error('addressLine') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="city" class="field-label mb-1">City</label>
                                <input id="city" type="text" wire:model="city" class="input">
                                @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="postal-code" class="field-label mb-1">Postal code</label>
                                <input id="postal-code" type="text" wire:model="postalCode" class="input">
                                @error('postalCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>
                @endif

                <section class="card p-5" wire:key="note-section">
                    <label for="order-note" class="field-label mb-1">Note for the store <span class="font-normal text-stone-400">(optional)</span></label>
                    <textarea id="order-note" rows="3" maxlength="500" wire:model="note" class="input resize-none"></textarea>
                    <p class="mt-1 text-xs text-stone-400">Up to 500 characters.</p>
                    @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </section>
            </div>

            <aside class="card self-start p-5 lg:sticky lg:top-24" wire:key="checkout-summary">
                <h2 class="font-display text-lg font-semibold text-stone-900">Summary</h2>

                <ul class="mt-4 divide-y divide-stone-100">
                    @foreach ($this->cartItems as $item)
                        <li class="flex items-start justify-between gap-3 py-3" wire:key="summary-{{ $item->id }}">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $item->variant->product->name }}</p>
                                <p class="text-xs text-stone-500">{{ $item->variant->label() }} · {{ $item->quantity }} ×</p>
                            </div>
                            <span class="shrink-0 text-sm font-medium tabular-nums text-stone-900">
                                {{ $price::format((int) $item->quantity * $item->variant->finalPrice()) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($this->stockIssues->isNotEmpty())
                    <div class="alert alert-warn mt-4" wire:key="stock-issues">
                        <p class="font-semibold">Stock changed while the cart waited</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($this->stockIssues as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-1">Sold-out rows cannot enter the order. Adjust the cart first.</p>
                    </div>
                @endif

                @error('cart')
                    <p class="alert alert-danger mt-4" wire:key="cart-error">{{ $message }}</p>
                @enderror

                <dl class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm text-stone-600">
                    <div class="flex items-baseline justify-between">
                        <dt>Subtotal</dt>
                        <dd class="font-medium tabular-nums text-stone-900">{{ $price::format($this->subtotal) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt>Shipping</dt>
                        <dd class="font-medium tabular-nums text-stone-900">{{ $price::format($this->shippingFee) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-stone-200 pt-3">
                        <dt class="font-medium text-stone-900">Total</dt>
                        <dd class="text-xl font-semibold tabular-nums text-stone-900">{{ $price::format($this->total) }}</dd>
                    </div>
                </dl>

                <button type="submit" class="btn-primary mt-5 w-full" @disabled($this->stockIssues->isNotEmpty())>
                    Place order
                </button>

                <p class="mt-3 text-xs text-stone-400">
                    Placing the order reserves the stock. The Midtrans payment step connects here in the next milestone.
                </p>
            </aside>
        </form>
    @endif
</div>
