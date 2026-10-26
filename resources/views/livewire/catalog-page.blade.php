<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-slate-900">Catalog</h1>
        <p class="mt-1 text-sm text-slate-500">Browse every active piece; filters update the list without a reload.</p>
    </header>

    <nav class="flex flex-wrap gap-2" wire:key="category-links">
        @foreach ($this->categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}"
               wire:key="category-{{ $category->slug }}"
               class="rounded-full border px-3 py-1 text-sm {{ $this->category === $category->slug ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-400' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </nav>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="space-y-5 rounded-lg border border-slate-200 bg-white p-4">
            <div>
                <label for="q" class="mb-1 block text-sm font-medium text-slate-700">Search</label>
                <input id="q" type="search" wire:model.live.debounce.350ms="q"
                       placeholder="Name or description…"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>

            <div>
                <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                <select id="category" wire:model="category"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                    <option value="">All categories</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Size</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->sizeOptions as $size)
                        <label class="cursor-pointer" wire:key="size-{{ $size }}">
                            <input type="checkbox" value="{{ $size }}" wire:model="sizes" class="peer sr-only">
                            <span class="inline-flex rounded-md border border-slate-300 px-3 py-1 text-sm text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white">
                                {{ $size }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Color</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->colorOptions as $color)
                        <label class="cursor-pointer" wire:key="color-{{ $color }}">
                            <input type="checkbox" value="{{ $color }}" wire:model="colors" class="peer sr-only">
                            <span class="inline-flex rounded-md border border-slate-300 px-3 py-1 text-sm text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white">
                                {{ $color }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Price range (IDR)</p>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" wire:model="minPrice" placeholder="Min"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                    <span class="text-slate-400">–</span>
                    <input type="number" min="0" wire:model="maxPrice" placeholder="Max"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                </div>
            </div>

            <button type="button" wire:click="clearFilters"
                    class="text-sm font-medium text-sky-700 hover:underline">
                Clear all filters
            </button>
        </aside>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    {{ $this->products->total() }} product(s)
                </p>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    Sort
                    <select wire:model="sort"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                        <option value="newest">Newest</option>
                        <option value="price_low">Price low to high</option>
                        <option value="price_high">Price high to low</option>
                    </select>
                </label>
            </div>

            @if ($this->products->isEmpty())
                <p class="rounded-lg border border-slate-200 bg-white p-10 text-center text-slate-500">
                    No products match these filters.
                </p>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products as $product)
                        <x-product-card :product="$product" wire:key="product-{{ $product->id }}"/>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $this->products->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
