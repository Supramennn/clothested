<div class="space-y-6">
    <header class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Cart</h1>
            <p class="mt-1 text-sm text-slate-500">Each row is one size and color; quantities clamp to stock and 30.</p>
        </div>
        <span class="text-sm text-slate-400" wire:loading.delay>Updating…</span>
    </header>

    @if ($notice)
        <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" wire:key="cart-notice">{{ $notice }}</p>
    @endif

    @if ($this->cartItems->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-10 text-center" wire:key="cart-empty">
            <p class="text-lg font-semibold text-slate-900">Your cart is empty</p>
            <p class="mt-1 text-sm text-slate-500">Pick a product from the catalog and save a size to it.</p>
            <a href="{{ route('home') }}"
               class="mt-5 inline-block rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                Browse the catalog
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white" wire:key="cart-rows">
            <ul>
                @foreach ($this->cartItems as $item)
                    <li class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-4 last:border-b-0"
                        wire:key="cart-row-{{ $item->id }}">
                        <img src="{{ $item->variant->product->images->first()?->url() }}"
                             alt="{{ $item->variant->product->name }}"
                             class="h-20 w-16 shrink-0 rounded-md border border-slate-200 object-cover">

                        <div class="w-40 min-w-32 grow">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                {{ $item->variant->product->category?->name }}
                            </p>
                            <a href="{{ route('products.show', $item->variant->product->slug) }}"
                               class="font-semibold text-slate-900 hover:text-sky-700">
                                {{ $item->variant->product->name }}
                            </a>
                            <p class="text-sm text-slate-500">{{ $item->variant->label() }}</p>
                        </div>

                        <div class="w-24 text-right text-sm text-slate-500">
                            Rp {{ number_format((float) $item->variant->finalPrice(), 0, ',', '.') }}
                            <span class="block text-xs text-slate-400">per piece</span>
                        </div>

                        <div>
                            <label for="qty-{{ $item->id }}" class="mb-1 block text-xs text-slate-400">Qty</label>
                            <input id="qty-{{ $item->id }}" type="number" min="0" max="30"
                                   wire:model.live="quantities.{{ $item->id }}"
                                   @if ((int) $item->variant->stock < 1) disabled @endif
                                   class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                        </div>

                        <div class="w-28 text-right">
                            <span class="font-semibold text-slate-900">
                                Rp {{ number_format($item->quantity * (float) $item->variant->finalPrice(), 0, ',', '.') }}
                            </span>
                        </div>

                        <div>
                            <button type="button" wire:click="removeItem({{ $item->id }})"
                                    class="text-sm font-medium text-red-600 hover:underline">
                                Remove
                            </button>
                        </div>

                        @if ((int) $item->variant->stock < 1)
                            <p class="w-full text-sm text-red-600" wire:key="stock-zero-{{ $item->id }}">
                                This size and color is out of stock; remove it or pick another one.
                            </p>
                        @elseif ($item->quantity > (int) $item->variant->stock)
                            <p class="w-full text-sm text-amber-700" wire:key="stock-low-{{ $item->id }}">
                                Stock changed: only {{ $item->variant->stock }} left for this row.
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4"
             wire:key="cart-summary">
            <div>
                <p class="text-sm text-slate-500">Subtotal</p>
                <p class="text-2xl font-bold text-slate-900">
                    Rp {{ number_format($this->subtotal, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    The shipping fee shows up at checkout; checkout arrives in the next phase.
                </p>
            </div>
        </div>
    @endif
</div>
