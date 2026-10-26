<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Wishlist</h1>
        <p class="mt-1 text-sm text-slate-500">Saved products stay here until you move or remove them.</p>
    </div>

    @if ($notice)
        <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" wire:key="wishlist-notice">{{ $notice }}</p>
    @endif

    @if ($this->items->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-10 text-center" wire:key="wishlist-empty">
            <p class="text-lg font-semibold text-slate-900">Nothing saved yet</p>
            <p class="mt-1 text-sm text-slate-500">Open a product page and save it to keep it here.</p>
            <a href="{{ route('home') }}"
               class="mt-5 inline-block rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                Browse the catalog
            </a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2" wire:key="wishlist-rows">
            @foreach ($this->items as $row)
                <article class="flex gap-4 rounded-lg border border-slate-200 bg-white p-4"
                         wire:key="wishlist-{{ $row->id }}">
                    <img src="{{ $row->product->images->first()?->url() }}"
                         alt="{{ $row->product->name }}"
                         class="h-28 w-24 shrink-0 rounded-md border border-slate-200 object-cover">

                    <div class="grow">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            {{ $row->product->category?->name }}
                        </p>
                        <a href="{{ route('products.show', $row->product->slug) }}"
                           class="font-semibold text-slate-900 hover:text-sky-700">
                            {{ $row->product->name }}
                        </a>
                        <p class="mt-1 text-sm text-slate-500">
                            from Rp {{ number_format((float) $row->product->base_price, 0, ',', '.') }}
                        </p>

                        @if (! $row->product->is_active)
                            <p class="mt-2 text-sm text-slate-500">No longer on sale.</p>
                        @elseif ($row->product->variants->first(fn ($v): bool => (int) $v->stock > 0) === null)
                            <p class="mt-2 text-sm text-slate-500">Out of stock for now.</p>
                        @else
                            <p class="mt-2 text-sm text-emerald-700">In stock</p>
                        @endif

                        <div class="mt-3 flex flex-wrap gap-3">
                            <button type="button" wire:click="moveToCart({{ $row->id }})"
                                    @if (! $row->product->is_active
                                        || $row->product->variants->first(fn ($v): bool => (int) $v->stock > 0) === null) disabled @endif
                                    class="rounded-md px-4 py-2 text-sm font-semibold text-white {{ (! $row->product->is_active || $row->product->variants->first(fn ($v): bool => (int) $v->stock > 0) === null) ? 'cursor-not-allowed bg-slate-300' : 'bg-sky-600 hover:bg-sky-500' }}">
                                Move to cart
                            </button>

                            <button type="button" wire:click="remove({{ $row->id }})"
                                    class="text-sm font-medium text-red-600 hover:underline">
                                Remove
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
