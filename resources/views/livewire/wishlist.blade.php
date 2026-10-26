<div class="space-y-6">
    <header>
        <h1 class="font-display text-3xl font-semibold tracking-tight text-stone-900">Wishlist</h1>
        <p class="mt-1 text-sm text-stone-500">Saved products stay here until you move or remove them.</p>
    </header>

    @if ($notice)
        <p class="alert alert-warn" wire:key="wishlist-notice">{{ $notice }}</p>
    @endif

    @if ($this->items->isEmpty())
        <div class="flex flex-col items-center rounded-xl border border-stone-200 bg-white px-6 py-16 text-center" wire:key="wishlist-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" class="h-10 w-10 text-stone-300" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
            </svg>
            <p class="mt-4 font-display text-xl font-semibold text-stone-900">Nothing saved yet</p>
            <p class="mt-2 max-w-sm text-sm text-stone-500">
                Open a product page and tap the heart to keep it here.
            </p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">Browse the catalog</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" wire:key="wishlist-rows">
            @foreach ($this->items as $row)
                @php($stocked = $row->product->is_active
                    && $row->product->variants->first(fn ($v): bool => (int) $v->stock > 0) !== null)
                <article class="flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white"
                         wire:key="wishlist-{{ $row->id }}">
                    <a href="{{ route('products.show', $row->product->slug) }}" class="block bg-stone-100" aria-label="{{ $row->product->name }}">
                        <img src="{{ $row->product->images->first()?->url() }}"
                             alt="{{ $row->product->name }}"
                             class="aspect-[4/5] w-full object-cover">
                    </a>

                    <div class="flex grow flex-col p-4">
                        <p class="eyebrow">{{ $row->product->category?->name }}</p>
                        <a href="{{ route('products.show', $row->product->slug) }}"
                           class="mt-1 font-display text-lg leading-snug text-stone-900 transition-colors hover:text-stone-950 hover:underline">
                            {{ $row->product->name }}
                        </a>
                        <p class="mt-1 text-sm text-stone-500">
                            Starting from
                            <span class="font-semibold text-stone-900 tabular-nums">
                                Rp {{ number_format((float) $row->product->base_price, 0, ',', '.') }}
                            </span>
                        </p>

                        @if (! $row->product->is_active)
                            <p class="mt-2 text-sm text-stone-500">No longer on sale.</p>
                        @elseif (! $stocked)
                            <p class="mt-2 text-sm text-stone-500">Out of stock for now.</p>
                        @else
                            <p class="mt-2 text-sm font-medium text-emerald-700">In stock</p>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center gap-3 pt-4">
                            <button type="button" wire:click="moveToCart({{ $row->id }})"
                                    @if (! $stocked) disabled @endif
                                    class="btn-primary px-4 py-2">
                                Move to cart
                            </button>

                            <button type="button" wire:click="remove({{ $row->id }})"
                                    class="text-sm font-medium text-stone-400 transition-colors hover:text-red-600">
                                Remove
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
