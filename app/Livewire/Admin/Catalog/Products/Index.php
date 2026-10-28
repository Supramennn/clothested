<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Products · ClothesTed')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, string|int> */
    public array $stockValues = [];

    public ?string $notice = null;

    public function mount(): void
    {
        $this->syncStockValues();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'variants'])
            ->when($this->search !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes($this->search, '%_\\').'%'))
            ->orderByDesc('id')
            ->paginate(12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->syncStockValues();
    }

    public function updatedPage(): void
    {
        $this->syncStockValues();
    }

    public function adjustStock(int $variantId): void
    {
        $this->validate([
            'stockValues.'.$variantId => ['required', 'integer', 'min:0'],
        ]);

        $stock = (int) $this->stockValues[$variantId];

        $variant = DB::transaction(function () use ($variantId, $stock): ProductVariant {
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variantId);
            $variant->update(['stock' => $stock]);

            return $variant->refresh();
        });

        $this->stockValues[$variantId] = $variant->stock;
        $this->notice = 'Stock updated for '.$variant->sku.'.';
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        if ($product->variants()
            ->where(fn ($query) => $query->whereHas('orderItems')->orWhereHas('cartItems'))
            ->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Products in an order or customer cart cannot be deleted. Deactivate the product instead.',
            ]);
        }

        $product->delete();
        unset($this->products);
        $this->notice = 'Product deleted.';
    }

    private function syncStockValues(): void
    {
        $this->stockValues = $this->products->getCollection()
            ->flatMap(fn (Product $product) => $product->variants->mapWithKeys(
                fn (ProductVariant $variant): array => [$variant->id => $variant->stock]
            ))
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.products.index');
    }
}
