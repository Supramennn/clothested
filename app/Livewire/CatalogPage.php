<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Class 3.4: CatalogPage (search, category, size, color, price, sort).
 * The storefront home page and the category pages; filters live in the URL.
 */
#[Layout('layouts.app')]
class CatalogPage extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $category = '';

    #[Url]
    public array $sizes = [];

    #[Url]
    public array $colors = [];

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    #[Url]
    public string $sort = 'newest';

    /**
     * Category page entry (FR-16); unknown slugs are treated as missing.
     */
    public function mount(?string $slug = null): void
    {
        if ($slug !== null) {
            Category::query()->where('slug', $slug)->firstOr(fn () => abort(404));

            $this->category = $slug;
        }
    }

    /**
     * FR-14: filter changes replace the list without a full page reload,
     * with the state mirrored into the query string for shareable links.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->ordered()->get();
    }

    /**
     * FR-10/FR-09: only active products, first image per card, filters AND-ed.
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with(['category']);

        if ($this->q !== '') {
            $needle = '%'.addcslashes($this->q, '%_\\').'%';

            $query->where(fn (Builder $builder) => $builder
                ->whereLike('name', $needle)
                ->orWhereLike('description', $needle));
        }

        if ($this->category !== '') {
            $query->whereHas('category', fn (Builder $builder) => $builder->where('slug', $this->category));
        }

        $sizeFilter = $this->allowListed($this->sizes, $this->sizeOptions());
        $colorFilter = $this->allowListed($this->colors, $this->colorOptions());

        if ($sizeFilter !== []) {
            $query->whereHas('variants', fn (Builder $builder) => $builder->whereIn('size', $sizeFilter));
        }

        if ($colorFilter !== []) {
            $query->whereHas('variants', fn (Builder $builder) => $builder->whereIn('color', $colorFilter));
        }

        if (is_int($this->minPrice)) {
            $query->where('base_price', '>=', $this->minPrice);
        }

        if (is_int($this->maxPrice)) {
            $query->where('base_price', '<=', $this->maxPrice);
        }

        match ($this->sort) {
            'price_low' => $query->orderBy('base_price'),
            'price_high' => $query->orderByDesc('base_price'),
            default => $query->orderByDesc('id'), // FR-13: newest is the default
        };

        return $query->paginate(24); // FR-15
    }

    /**
     * Size chips available across the active catalog, in S/M/L/XL order.
     *
     * @return list<string>
     */
    #[Computed]
    public function sizeOptions(): array
    {
        $implicit = ['S', 'M', 'L', 'XL'];

        return ProductVariant::query()
            ->whereIn('product_id', Product::query()->active()->select('id'))
            ->pluck('size')
            ->unique()
            ->sortBy(fn (string $size) => array_search($size, $implicit, true) ?? 99)
            ->values()
            ->all();
    }

    /**
     * Color chips available across the active catalog.
     *
     * @return list<string>
     */
    #[Computed]
    public function colorOptions(): array
    {
        return ProductVariant::query()
            ->whereIn('product_id', Product::query()->active()->select('id'))
            ->pluck('color')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * FR-13: switch the ordering without changing the rest of the state.
     */
    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSizes(): void
    {
        $this->resetPage();
    }

    public function updatedColors(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    /**
     * Drop the whole filter set back to the defaults.
     */
    public function clearFilters(): void
    {
        $this->q = '';
        $this->category = '';
        $this->sizes = [];
        $this->colors = [];
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->sort = 'newest';

        $this->resetPage();
    }

    /**
     * FR-12: keep only URL values that map onto a live option (compared
     * case-insensitively) and return them in their canonical casing.
     *
     * @param  list<string>  $selected
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function allowListed(array $selected, array $allowed): array
    {
        $canonicalByLower = [];

        foreach ($allowed as $allowedValue) {
            $canonicalByLower[mb_strtolower(trim($allowedValue))] = $allowedValue;
        }

        $mapped = array_map(function (mixed $value) use ($canonicalByLower): ?string {
            $needle = is_string($value) ? mb_strtolower(trim($value)) : '';

            return $canonicalByLower[$needle] ?? null;
        }, $selected);

        return array_values(array_filter($mapped, fn (?string $value): bool => $value !== null));
    }

    public function render(): View
    {
        return view('livewire.catalog-page');
    }
}
