<div class="space-y-6">
    <x-admin-nav />

    <header>
        <p class="eyebrow">Catalog</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">
            {{ $productId === null ? 'Add product' : 'Edit product' }}
        </h1>
        <p class="mt-2 text-sm text-stone-600">Product details and purchasable SKUs.</p>
    </header>

    <form wire:submit="save" class="space-y-6">
        <section class="card grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <div>
                <label for="product-name" class="field-label">Name</label>
                <input id="product-name" type="text" wire:model="name" class="input mt-1 min-h-11">
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="product-slug" class="field-label">Slug</label>
                <input id="product-slug" type="text" wire:model="slug" class="input mt-1 min-h-11">
                @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="product-category" class="field-label">Category</label>
                <select id="product-category" wire:model="categoryId" class="input mt-1 min-h-11">
                    <option value="">Choose a category</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="product-price" class="field-label">Base price (IDR)</label>
                <input id="product-price" type="number" min="0" step="0.01" wire:model="basePrice" class="input mt-1 min-h-11">
                @error('basePrice') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="product-description" class="field-label">Description</label>
                <textarea id="product-description" wire:model="description" rows="4" class="input mt-1"></textarea>
                @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <fieldset class="flex flex-wrap gap-x-6 gap-y-3 sm:col-span-2">
                <legend class="field-label mb-2">Storefront status</legend>
                <label class="inline-flex min-h-11 items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" wire:model="isActive" class="size-4 accent-sky-700">
                    Published
                </label>
                <label class="inline-flex min-h-11 items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" wire:model="isFeatured" class="size-4 accent-sky-700">
                    Featured
                </label>
            </fieldset>
        </section>

        <section class="card overflow-hidden">
            <header class="flex flex-wrap items-end justify-between gap-3 border-b border-stone-200 p-5 sm:p-6">
                <div>
                    <h2 class="font-display text-xl font-semibold text-stone-900">SKUs</h2>
                    <p class="mt-1 text-sm text-stone-600">Each size and color combination needs a unique SKU.</p>
                </div>
                <button type="button" wire:click="addVariant" class="btn-outline min-h-11">Add SKU</button>
            </header>

            <div class="space-y-4 p-4 sm:p-5">
                @error('variants')
                    <p class="alert alert-danger" role="alert">{{ $message }}</p>
                @enderror

                @forelse ($variants as $index => $variant)
                    <fieldset wire:key="variant-form-{{ $variant['id'] ?? 'new-'.$index }}" class="rounded-lg border border-stone-200 p-4">
                        <legend class="sr-only">SKU {{ $index + 1 }}</legend>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <label for="variant-sku-{{ $index }}" class="field-label">SKU</label>
                                <input id="variant-sku-{{ $index }}" type="text" wire:model="variants.{{ $index }}.sku" class="input mt-1 min-h-11">
                                @error('variants.'.$index.'.sku') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="variant-size-{{ $index }}" class="field-label">Size</label>
                                <select id="variant-size-{{ $index }}" wire:model="variants.{{ $index }}.size" class="input mt-1 min-h-11">
                                    @foreach (['S', 'M', 'L', 'XL'] as $size)
                                        <option value="{{ $size }}">{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('variants.'.$index.'.size') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="variant-color-{{ $index }}" class="field-label">Color</label>
                                <input id="variant-color-{{ $index }}" type="text" wire:model="variants.{{ $index }}.color" class="input mt-1 min-h-11">
                                @error('variants.'.$index.'.color') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="variant-price-{{ $index }}" class="field-label">Price difference</label>
                                <input id="variant-price-{{ $index }}" type="number" min="0" step="0.01"
                                       wire:model="variants.{{ $index }}.additional_price" class="input mt-1 min-h-11">
                                @error('variants.'.$index.'.additional_price') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                @if (isset($variant['id']))
                                    <span class="field-label">Current stock</span>
                                    <p class="mt-1 flex min-h-11 items-center text-sm font-medium text-stone-900">{{ $variant['stock'] }}</p>
                                    <p class="text-xs text-stone-600">Adjust stock from the product list.</p>
                                @else
                                    <label for="variant-stock-{{ $index }}" class="field-label">Opening stock</label>
                                    <input id="variant-stock-{{ $index }}" type="number" min="0"
                                           wire:model="variants.{{ $index }}.stock" class="input mt-1 min-h-11">
                                    @error('variants.'.$index.'.stock') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="removeVariant({{ $index }})"
                                class="btn-ghost mt-3 inline-flex min-h-11 items-center text-red-700">
                            Remove SKU
                        </button>
                    </fieldset>
                @empty
                    <div class="rounded-lg border border-dashed border-stone-300 px-5 py-8 text-center">
                        <p class="font-medium text-stone-900">No SKUs added</p>
                        <p class="mt-1 text-sm text-stone-600">A published product needs at least one SKU.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-primary min-h-11" wire:loading.attr="disabled">Save product</button>
            <a href="{{ route('admin.products.index') }}" class="btn-outline min-h-11">Cancel</a>
        </div>
    </form>

    <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Saving product changes…</p>
</div>
