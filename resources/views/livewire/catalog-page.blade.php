@section('title', $this->activeCategoryName().' · ClothesTed')
@section('meta_description', $this->activeCategoryName() === 'Catalog'
    ? 'New arrivals and wardrobe staples, filter by size, color, and price without leaving the page.'
    : 'Shop the '.$this->activeCategoryName().' collection with live filters by size, color, and price.')
<div class="space-y-10">
    <section class="rounded-xl bg-night px-6 py-16 text-white sm:px-12 sm:py-24" wire:key="hero">
        <p class="eyebrow-dark">The ClothesTed collection</p>
        <h1 class="type-display mt-4 max-w-3xl">
            {{ $this->activeCategoryName() === 'Catalog' ? 'Everyday pieces, sharply made.' : $this->activeCategoryName().' collection.' }}
        </h1>
        <p class="type-body-lg mt-6 max-w-xl text-zinc-300">
            New arrivals and wardrobe staples, filter by size, color, and price without leaving the page.
        </p>
        <a href="#catalog" class="btn-outline-dark mt-10">Browse the catalog</a>
    </section>

    <nav class="flex flex-wrap gap-2" wire:key="category-links" aria-label="Categories">
        @foreach ($this->categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}"
               wire:key="category-{{ $category->slug }}"
               class="rounded-full border px-4 py-1.5 text-sm transition-colors {{ $this->category === $category->slug ? 'border-black bg-black text-white' : 'border-zinc-300 bg-white text-zinc-600 hover:border-zinc-500' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </nav>

    <div id="catalog" class="grid gap-6 scroll-mt-24 lg:grid-cols-[260px_1fr]">
        <aside class="card self-start lg:sticky lg:top-24" wire:key="filters">
            <div class="border-b border-zinc-200 p-4">
                <label for="q" class="field-label mb-1">Search</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" class="pointer-events-none absolute top-3 left-3 h-4 w-4 text-zinc-400" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input id="q" type="search" wire:model.live.debounce.350ms="q"
                           placeholder="Name or description…"
                           class="input pl-9">
                </div>
            </div>

            <div class="border-b border-zinc-200 p-4">
                <label for="category" class="field-label mb-1">Category</label>
                <select id="category" wire:model="category" class="input">
                    <option value="">All categories</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="border-b border-zinc-200 p-4">
                <p class="field-label mb-2">Size</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->sizeOptions as $size)
                        <label class="cursor-pointer" wire:key="size-{{ $size }}">
                            <input type="checkbox" value="{{ $size }}" wire:model="sizes" class="peer sr-only">
                            <span class="inline-flex rounded-full border px-3 py-1 text-sm text-zinc-600 transition-colors
                                        border-zinc-300 hover:border-zinc-500
                                        peer-checked:border-black peer-checked:bg-black peer-checked:text-white
                                        focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
                                {{ $size }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="border-b border-zinc-200 p-4">
                <p class="field-label mb-2">Color</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->colorOptions as $color)
                        <label class="cursor-pointer" wire:key="color-{{ $color }}">
                            <input type="checkbox" value="{{ $color }}" wire:model="colors" class="peer sr-only">
                            <span class="inline-flex rounded-full border px-3 py-1 text-sm text-zinc-600 transition-colors
                                        border-zinc-300 hover:border-zinc-500
                                        peer-checked:border-black peer-checked:bg-black peer-checked:text-white
                                        focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
                                {{ $color }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="p-4">
                <p class="field-label mb-2">Price range (IDR)</p>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" wire:model="minPrice" placeholder="Min" class="input" aria-label="Minimum price">
                    <span class="text-zinc-400" aria-hidden="true">–</span>
                    <input type="number" min="0" wire:model="maxPrice" placeholder="Max" class="input" aria-label="Maximum price">
                </div>
            </div>
        </aside>

        <section>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-zinc-500">
                    {{ $this->products->total() }} {{ $this->products->total() === 1 ? 'product' : 'products' }}
                </p>
                <label class="flex items-center gap-2 text-sm text-zinc-600">
                    Sort
                    <div class="relative">
                        <select wire:model="sort"
                                class="input w-auto appearance-none pr-8 font-medium text-black">
                            <option value="newest">Newest</option>
                            <option value="price_low">Price low to high</option>
                            <option value="price_high">Price high to low</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" class="pointer-events-none absolute top-3 right-2.5 h-4 w-4 text-zinc-400" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </div>
                </label>
            </div>

            @if ($this->products->isEmpty())
                <div class="card flex flex-col items-center px-6 py-14 text-center">
                    <p class="type-heading-lg text-black">No products match these filters</p>
                    <p class="mt-2 max-w-sm text-sm text-zinc-500">
                        The search term, sizes, colors, or price span may be too narrow. Clearing a filter brings the row back.
                    </p>
                    <button type="button" wire:click="clearFilters" class="btn-outline mt-6">Clear all filters</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-5 gap-y-8 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products as $product)
                        <x-product-card :product="$product" wire:key="product-{{ $product->id }}"/>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-center">
                    {{ $this->products->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
