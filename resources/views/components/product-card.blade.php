@props(['product'])

<a href="{{ route('products.show', $product->slug) }}"
   class="group block overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-sky-300 hover:shadow-md">
    <div class="relative bg-sky-50">
        <img src="{{ $product->images->first()?->url() }}"
             alt="{{ $product->name }}"
             class="aspect-[4/5] w-full object-cover">
        @if ($product->is_featured)
            <span class="absolute top-2 left-2 rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white">
                Featured
            </span>
        @endif
    </div>

    <div class="space-y-1 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $product->category?->name }}</p>
        <h3 class="font-semibold text-slate-900 group-hover:text-sky-700">{{ $product->name }}</h3>
        <p class="text-sm text-slate-700">
            <span class="font-semibold text-slate-900">Rp {{ number_format((float) $product->base_price, 0, ',', '.') }}</span>
            <span class="text-xs text-slate-400">starting from</span>
        </p>
    </div>
</a>
