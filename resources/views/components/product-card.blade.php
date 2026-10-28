@props(['product'])

<a href="{{ route('products.show', $product->slug) }}"
   class="group flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white transition-colors hover:border-zinc-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
    <div class="relative overflow-hidden bg-zinc-100">
        <img src="{{ $product->images->first()?->url() }}"
             alt="{{ $product->name }}"
             class="aspect-[4/5] w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
        @if ($product->is_featured)
            <span class="absolute top-3 left-3 rounded-full bg-aloe px-3 py-1 text-xs font-medium tracking-[0.06em] text-black">
                Featured
            </span>
        @endif
    </div>

    <div class="px-4 py-4">
        <p class="eyebrow">{{ $product->category?->name }}</p>
        <h3 class="mt-1 font-display text-lg font-[450] leading-snug text-black">{{ $product->name }}</h3>
        <p class="mt-2 text-sm text-zinc-500">
            Starting from
            <span class="font-[550] text-black tabular-nums">
                Rp {{ number_format((float) $product->base_price, 0, ',', '.') }}
            </span>
        </p>
    </div>
</a>
