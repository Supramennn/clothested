{{--
    Product detail page: breadcrumb (FR-17), gallery in sort_order,
    side-by-side picker (FR-18), live final price (FR-19), cart (FR-20),
    wishlist toggle (FR-21). SEO: per-page title, description, and the
    Product structured data of the nonfunctional requirements.
--}}
@section('title', $product->name.' — ClothesTed')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))
<script type="application/ld+json" wire:key="product-structured-data"
>{!! json_encode($this->structuredData(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
<div class="space-y-8">
    <nav class="text-sm text-stone-500" aria-label="Breadcrumb" wire:key="breadcrumb">
        <a href="{{ route('home') }}" class="transition-colors hover:text-stone-950 hover:underline">Home</a>
        <span class="mx-1.5 text-stone-300" aria-hidden="true">/</span>
        <a href="{{ route('categories.show', $product->category->slug) }}" class="transition-colors hover:text-stone-950 hover:underline">
            {{ $product->category->name }}
        </a>
        <span class="mx-1.5 text-stone-300" aria-hidden="true">/</span>
        <span class="text-stone-900">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2 lg:gap-14">
        <section class="space-y-3" wire:key="gallery">
            <div class="overflow-hidden rounded-xl border border-stone-200 bg-white" wire:key="main-image">
                <img src="{{ $this->displayedImage?->url() }}"
                     alt="{{ $product->name }} — image"
                     class="aspect-[4/5] w-full object-cover">
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-5 gap-2 sm:grid-cols-6">
                    @foreach ($product->images as $image)
                        <button type="button" wire:click="selectImage({{ $image->id }})"
                                wire:key="image-{{ $image->id }}"
                                @class(['overflow-hidden rounded-lg border transition-colors',
                                    $this->displayedImage?->id === $image->id
                                        ? 'border-stone-900'
                                        : 'border-stone-200 hover:border-stone-400'])
                                aria-label="Show image {{ $image->sort_order + 1 }}"
                                aria-pressed="{{ $this->displayedImage?->id === $image->id ? 'true' : 'false' }}">
                            <img src="{{ $image->url() }}"
                                 alt="{{ $product->name }} thumbnail {{ $image->sort_order + 1 }}"
                                 class="aspect-square w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-5">
            <div>
                <p class="eyebrow">{{ $product->category->name }}</p>
                <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight text-stone-900" wire:key="product-name">
                    {{ $product->name }}
                </h1>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-stone-900">
                    Rp {{ number_format($this->finalPrice, 0, ',', '.') }}
                </p>
            </div>

            <div class="border-t border-stone-200 pt-5">
                <p class="field-label mb-2">Color</p>
                <div class="flex flex-wrap gap-2" wire:key="color-options">
                    @foreach ($this->colorOptions as $color)
                        <button type="button" wire:click="selectColor('{{ $color }}')"
                                @class(['rounded-md border px-3 py-1.5 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700',
                                    $this->color === $color
                                        ? 'border-stone-900 bg-stone-900 text-white'
                                        : 'border-stone-300 text-stone-700 hover:border-stone-400'])
                                aria-pressed="{{ $this->color === $color ? 'true' : 'false' }}">
                            {{ $color }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-stone-200 pt-5">
                <p class="field-label mb-2">Size</p>
                <div class="flex flex-wrap gap-2" wire:key="size-options">
                    @foreach ($this->sizeOptions as $size)
                        <button type="button" wire:click="selectSize('{{ $size }}')"
                                @class(['rounded-md border px-3 py-1.5 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700',
                                    $this->size === $size
                                        ? 'border-stone-900 bg-stone-900 text-white'
                                        : 'border-stone-300 text-stone-700 hover:border-stone-400'])
                                aria-pressed="{{ $this->size === $size ? 'true' : 'false' }}">
                            {{ $size }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-stone-200 pt-5">
                <div class="flex items-center gap-2 text-sm" wire:key="stock-line">
                    @if ($this->inStock)
                        <span class="font-semibold text-emerald-700">In stock</span>
                        <span class="text-stone-500">{{ $this->selectedVariant?->stock }} available</span>
                    @else
                        <span class="font-semibold text-red-600">Out of stock</span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3" wire:key="actions">
                    <button type="button" wire:click="addToCart" @unless ($this->inStock) disabled @endunless class="btn-primary">
                        {{ $this->inStock ? 'Add to cart' : 'Out of stock' }}
                    </button>

                    <button type="button" wire:click="toggleWishlist" @class(['btn-outline gap-2'])>
                        @if ($this->inWishlist)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                 class="h-4 w-4 text-red-600" aria-hidden="true">
                                <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
                            </svg>
                            Saved
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" class="h-4 w-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                            </svg>
                            Save to wishlist
                        @endif
                    </button>
                </div>

                @if ($this->addedMessage)
                    <p class="alert alert-success mt-4" wire:key="added">{{ $this->addedMessage }}</p>
                @endif
            </div>

            <div class="border-t border-stone-200 pt-5">
                <p class="leading-relaxed text-stone-600">{{ $product->description }}</p>
            </div>
        </section>
    </div>
</div>
