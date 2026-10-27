<div class="space-y-6">
    <header class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-stone-900">Cart</h1>
            <p class="mt-1 text-sm text-stone-500">Each row is one size and color; quantities clamp to stock and 30.</p>
        </div>
        <span class="text-sm text-stone-400" wire:loading.delay>Updating…</span>
    </header>

    @if ($notice)
        <p class="alert alert-warn" wire:key="cart-notice">{{ $notice }}</p>
    @endif

    @if ($this->cartItems->isEmpty())
        <div class="flex flex-col items-center rounded-xl border border-stone-200 bg-white px-6 py-16 text-center" wire:key="cart-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" class="h-10 w-10 text-stone-300" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
            </svg>
            <p class="mt-4 font-display text-xl font-semibold text-stone-900">Your cart is empty</p>
            <p class="mt-2 max-w-sm text-sm text-stone-500">
                Pick a product from the catalog and save a size to it.
            </p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Browse the catalog</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <ul class="self-start divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white" wire:key="cart-rows">
                @foreach ($this->cartItems as $item)
                    <li class="grid grid-cols-[88px_1fr] items-start gap-4 p-4 sm:grid-cols-[96px_1fr_170px_64px] sm:items-center"
                        wire:key="cart-row-{{ $item->id }}">
                        <img src="{{ $item->variant->product->images->first()?->url() }}"
                             alt="{{ $item->variant->product->name }}"
                             class="h-28 w-[88px] rounded-lg border border-stone-200 object-cover sm:h-24 sm:w-24">

                        <div class="min-w-40 grow">
                            <p class="eyebrow">{{ $item->variant->product->category?->name }}</p>
                            <a href="{{ route('products.show', $item->variant->product->slug) }}"
                               class="mt-0.5 font-medium text-stone-900 transition-colors hover:text-stone-950 hover:underline">
                                {{ $item->variant->product->name }}
                            </a>
                            <p class="text-sm text-stone-500">{{ $item->variant->label() }}</p>
                        </div>

                        <div class="col-span-2 flex items-center justify-between gap-6 sm:col-span-1 sm:justify-end">
                            <div class="flex items-center gap-1.5" wire:key="qty-{{ $item->id }}">
                                <button type="button" wire:click="setQty({{ $item->id }}, {{ max(1, (int) $item->quantity - 1) }})"
                                        @if ((int) $item->quantity <= 1) disabled @endif
                                        class="h-8 w-8 rounded-md border border-stone-300 text-sm font-semibold text-stone-600 transition-colors hover:border-stone-400 hover:text-stone-900 disabled:pointer-events-none disabled:border-stone-200 disabled:text-stone-300"
                                        aria-label="Decrease quantity">
                                    &minus;
                                </button>
                                <input type="number" min="1" max="30" wire:model.live="quantities.{{ $item->id }}"
                                       @if ((int) $item->variant->stock < 1) disabled @endif
                                       class="input w-14 px-2 text-center"
                                       aria-label="Quantity for {{ $item->variant->product->name }}">
                                <button type="button" wire:click="setQty({{ $item->id }}, {{ min(30, (int) $item->quantity + 1) }})"
                                        class="h-8 w-8 rounded-md border border-stone-300 text-sm font-semibold text-stone-600 transition-colors hover:border-stone-400 hover:text-stone-900 disabled:pointer-events-none disabled:border-stone-200 disabled:text-stone-300"
                                        aria-label="Increase quantity">
                                    +
                                </button>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold tabular-nums text-stone-900">
                                    Rp {{ number_format($item->quantity * (float) $item->variant->finalPrice(), 0, ',', '.') }}
                                </p>
                                <p class="text-xs tabular-nums text-stone-400">
                                    Rp {{ number_format((float) $item->variant->finalPrice(), 0, ',', '.') }} each
                                </p>
                            </div>
                        </div>

                        <div class="col-span-2 sm:col-span-1 sm:justify-self-end">
                            <button type="button" wire:click="removeItem({{ $item->id }})"
                                    class="text-sm font-medium text-stone-400 transition-colors hover:text-red-600">
                                Remove
                            </button>
                        </div>

                        @if ((int) $item->variant->stock < 1)
                            <p class="col-span-full text-sm text-red-600" wire:key="stock-zero-{{ $item->id }}">
                                This size and color is out of stock; remove it or pick another one.
                            </p>
                        @elseif ($item->quantity > (int) $item->variant->stock)
                            <p class="col-span-full text-sm text-amber-700" wire:key="stock-low-{{ $item->id }}">
                                Stock changed: only {{ $item->variant->stock }} left for this row.
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>

            <aside class="self-start rounded-xl border border-stone-200 bg-white p-5 lg:sticky lg:top-24" wire:key="cart-summary">
                <h2 class="font-display text-lg font-semibold text-stone-900">Summary</h2>
                <div class="mt-4 flex items-baseline justify-between text-sm text-stone-600">
                    <span>Subtotal</span>
                    <span class="text-xl font-semibold tabular-nums text-stone-900">
                        Rp {{ number_format($this->subtotal, 0, ',', '.') }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-stone-400">
                    The shipping fee is added at checkout.
                </p>

                <a href="{{ route('checkout') }}" class="btn-primary mt-4 w-full">
                    Checkout
                </a>
            </aside>
        </div>
    @endif
</div>
