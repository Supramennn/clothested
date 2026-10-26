{{--
    Product detail page: breadcrumb (FR-17), gallery in sort_order,
    side-by-side picker (FR-18), live final price (FR-19), cart (FR-20),
    wishlist toggle (FR-21).
--}}
<div class="space-y-6">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb" wire:key="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-sky-700">Home</a>
        <span class="mx-1">/</span>
        <a href="{{ route('categories.show', $product->category->slug) }}" class="hover:text-sky-700">
            {{ $product->category->name }}
        </a>
        <span class="mx-1">/</span>
        <span class="text-slate-700">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="space-y-3" wire:key="gallery">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <img src="{{ $product->images->first()?->url() }}"
                     alt="{{ $product->name }} gallery image"
                     class="aspect-[4/5] w-full object-cover">
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($product->images as $image)
                        <img src="{{ $image->url() }}"
                             alt="{{ $product->name }} image {{ $image->sort_order + 1 }}"
                             class="aspect-square w-full rounded-md border border-slate-200 object-cover"
                             wire:key="image-{{ $image->id }}">
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400" wire:key="category-chip">
                    {{ $product->category->name }}
                </p>
                <h1 class="text-2xl font-bold text-slate-900" wire:key="product-name">{{ $product->name }}</h1>
                <p class="mt-2 text-3xl font-bold text-sky-700">
                    Rp {{ number_format($this->finalPrice, 0, ',', '.') }}
                </p>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Color</p>
                <div class="flex flex-wrap gap-2" wire:key="color-options">
                    @foreach ($this->colorOptions as $color)
                        <button type="button" wire:click="selectColor('{{ $color }}')"
                                class="rounded-md border px-3 py-1.5 text-sm {{ $this->color === $color ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-sky-400' }}">
                            {{ $color }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Size</p>
                <div class="flex flex-wrap gap-2" wire:key="size-options">
                    @foreach ($this->sizeOptions as $size)
                        <button type="button" wire:click="selectSize('{{ $size }}')"
                                class="rounded-md border px-3 py-1.5 text-sm {{ $this->size === $size ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-sky-400' }}">
                            {{ $size }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2 text-sm" wire:key="stock-line">
                @if ($this->inStock)
                    <span class="font-medium text-emerald-700">In stock</span>
                    <span class="text-slate-400">{{ $this->selectedVariant?->stock }} available</span>
                @else
                    <span class="font-medium text-red-600">Out of stock</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3" wire:key="actions">
                <button type="button" wire:click="addToCart" @unless ($this->inStock) disabled @endunless
                        class="rounded-md px-5 py-2.5 text-sm font-semibold text-white {{ $this->inStock ? 'bg-sky-600 hover:bg-sky-500' : 'cursor-not-allowed bg-slate-300 opacity-70' }}">
                    {{ $this->inStock ? 'Add to cart' : 'Out of stock' }}
                </button>

                <button type="button" wire:click="toggleWishlist"
                        class="rounded-md border px-5 py-2.5 text-sm font-medium {{ $this->inWishlist ? 'border-sky-600 bg-sky-50 text-sky-700' : 'border-slate-300 bg-white text-slate-600 hover:border-sky-400' }}">
                    {{ $this->inWishlist ? '♥ Saved' : '♡ Save to wishlist' }}
                </button>
            </div>

            @if ($this->addedMessage)
                <p class="text-sm text-emerald-700">{{ $this->addedMessage }}</p>
            @endif

            <p class="leading-relaxed text-slate-600">{{ $product->description }}</p>
        </section>
    </div>
</div>
