@props(['product'])

<a href="{{ route('products.show', $product->slug) }}"
   class="group flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white transition-colors hover:border-stone-400">
    <div class="relative overflow-hidden bg-stone-100">
        <img src="{{ $product->images->first()?->url() }}"
             alt="{{ $product->name }}"
             class="aspect-[4/5] w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
        @if ($product->is_featured)
            <span class="absolute top-3 left-3 rounded-full bg-stone-900/90 px-2.5 py-1 text-[11px] font-semibold tracking-wide text-white">
                Featured
            </span>
        @endif
    </div>

    <div class="px-4 py-4">
        <p class="eyebrow">{{ $product->category?->name }}</p>
        <h3 class="mt-1 font-display text-lg leading-snug text-stone-900">{{ $product->name }}</h3>
        <p class="mt-2 text-sm text-stone-500">
            Starting from
            <span class="font-semibold text-stone-900 tabular-nums">
                Rp {{ number_format((float) $product->base_price, 0, ',', '.') }}
            </span>
        </p>
    </div>
</a>
