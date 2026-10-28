<div class="space-y-6">
    <x-admin-nav />

    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="eyebrow">Catalog</p>
            <h1 class="type-display-lg mt-1 text-black">Products</h1>
            <p class="mt-2 text-sm text-zinc-600">Manage product details, SKUs, publication, and stock.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.categories') }}" class="btn-outline min-h-11">Manage categories</a>
            <a href="{{ route('admin.products.create') }}" class="btn-primary min-h-11">Add product</a>
        </div>
    </header>

    @if ($notice)
        <p class="alert alert-success" role="status">{{ $notice }}</p>
    @endif
    @error('product')
        <p class="alert alert-danger" role="alert">{{ $message }}</p>
    @enderror

    <section class="card p-4">
        <label for="product-search" class="field-label">Find a product</label>
        <input id="product-search" type="search" wire:model.live.debounce.300ms="search"
               class="input mt-1 min-h-11" placeholder="Search product names">
    </section>

    <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Loading products and stock…</p>

    @if ($this->products->isEmpty())
        <div class="card px-5 py-12 text-center">
            <h2 class="type-heading-lg text-black">
                {{ $search === '' ? 'No products yet' : 'No products match this search' }}
            </h2>
            <p class="mt-2 text-sm text-zinc-600">
                {{ $search === '' ? 'Add a product to start building the catalog.' : 'Try a different product name.' }}
            </p>
            @if ($search === '')
                <a href="{{ route('admin.products.create') }}" class="btn-primary mt-5 min-h-11">Add a product</a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->products as $product)
                <article wire:key="product-{{ $product->id }}" class="card overflow-hidden">
                    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200 p-4 sm:p-5">
                        <div class="min-w-0">
                            <p class="text-sm text-zinc-600">{{ $product->category->name }} · /{{ $product->slug }}</p>
                            <h2 class="type-heading-md mt-1 text-black">{{ $product->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ \App\Support\Price::format($product->base_price) }}
                                · {{ $product->is_active ? 'Published' : 'Draft' }}
                                @if ($product->is_featured) · Featured @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-outline min-h-11">Edit</a>
                            <button type="button" wire:click="deleteProduct({{ $product->id }})"
                                    wire:confirm="Delete this product and its SKUs?"
                                    class="btn-outline min-h-11 border-red-200 text-red-700 hover:border-red-300 hover:text-red-800">
                                Delete
                            </button>
                        </div>
                    </header>

                    @if ($product->variants->isEmpty())
                        <p class="px-4 py-4 text-sm text-zinc-600">No SKUs. Add a variant before publishing this product.</p>
                    @else
                        <ul class="divide-y divide-zinc-100">
                            @foreach ($product->variants as $variant)
                                <li wire:key="variant-{{ $variant->id }}" class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-5">
                                    <div class="min-w-0">
                                        <p class="font-medium text-zinc-900">{{ $variant->sku }}</p>
                                        <p class="mt-1 text-sm text-zinc-600">{{ $variant->color }} / {{ $variant->size }}</p>
                                    </div>
                                    <form wire:submit="adjustStock({{ $variant->id }})" class="flex flex-wrap items-end gap-2">
                                        <div>
                                            <label for="stock-{{ $variant->id }}" class="field-label">Stock</label>
                                            <input id="stock-{{ $variant->id }}" type="number" min="0"
                                                   wire:model="stockValues.{{ $variant->id }}"
                                                   class="input mt-1 min-h-11 w-28">
                                            @error('stockValues.'.$variant->id)
                                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn-outline min-h-11" wire:loading.attr="disabled">Set stock</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="flex justify-center">{{ $this->products->links() }}</div>
    @endif
</div>
