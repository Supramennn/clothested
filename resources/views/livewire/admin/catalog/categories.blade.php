<div class="space-y-6">
    <x-admin-nav />

    <header>
        <p class="eyebrow">Catalog</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Categories</h1>
        <p class="mt-2 text-sm text-stone-600">Create and maintain the groups shown in the storefront.</p>
    </header>

    @if ($notice)
        <p class="alert alert-success" role="status">{{ $notice }}</p>
    @endif
    @error('categories')
        <p class="alert alert-danger" role="alert">{{ $message }}</p>
    @enderror

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <section class="card p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold text-stone-900">
                {{ $editingCategoryId === null ? 'Add category' : 'Edit category' }}
            </h2>
            <form wire:submit="save" class="mt-5 space-y-4">
                <div>
                    <label for="category-name" class="field-label">Name</label>
                    <input id="category-name" type="text" wire:model="name" class="input mt-1 min-h-11">
                    @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category-slug" class="field-label">Slug</label>
                    <input id="category-slug" type="text" wire:model="slug" class="input mt-1 min-h-11">
                    <p class="mt-1 text-xs text-stone-600">Use a unique URL-friendly value.</p>
                    @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category-description" class="field-label">Description</label>
                    <textarea id="category-description" wire:model="description" rows="3" class="input mt-1"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary min-h-11" wire:loading.attr="disabled">
                        {{ $editingCategoryId === null ? 'Create category' : 'Save category' }}
                    </button>
                    @if ($editingCategoryId !== null)
                        <button type="button" wire:click="cancelEdit" class="btn-outline min-h-11">Cancel edit</button>
                    @endif
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-xl font-semibold text-stone-900">Existing categories</h2>
                <span class="text-sm text-stone-600">{{ $this->categories->count() }}</span>
            </div>
            <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Loading categories…</p>

            @if ($this->categories->isEmpty())
                <div class="card px-5 py-10 text-center">
                    <h3 class="font-display text-lg font-semibold text-stone-900">No categories yet</h3>
                    <p class="mt-2 text-sm text-stone-600">Add a category to organize products in the catalog.</p>
                </div>
            @else
                @foreach ($this->categories as $category)
                    <article wire:key="category-{{ $category->id }}" class="card flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <h3 class="font-medium text-stone-900">{{ $category->name }}</h3>
                            <p class="mt-1 break-all text-sm text-stone-600">/{{ $category->slug }} · {{ $category->products_count }} products</p>
                            @if ($category->description)
                                <p class="mt-2 text-sm text-stone-600">{{ $category->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="editCategory({{ $category->id }})" class="btn-outline min-h-11">Edit</button>
                            <button type="button" wire:click="deleteCategory({{ $category->id }})"
                                    wire:confirm="Delete this category?"
                                    class="btn-outline min-h-11 border-red-200 text-red-700 hover:border-red-300 hover:text-red-800">
                                Delete
                            </button>
                        </div>
                    </article>
                @endforeach
            @endif
        </section>
    </div>
</div>
