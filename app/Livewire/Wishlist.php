<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Wishlist as WishlistModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The wishlist page: saved products, move-to-cart, and remove.
 * The component name matches the architecture page list; the Eloquent model
 * is imported under a distinct alias.
 */
#[Layout('layouts.app')]
#[Title('Wishlist · ClothesTed')]
class Wishlist extends Component
{
    public ?string $notice = null;

    /**
     * FR-21/FR-31: the signed-in account wishlist, newest save first.
     *
     * @return Collection<int, WishlistModel>
     */
    #[Computed]
    public function items(): Collection
    {
        return WishlistModel::query()
            ->where('user_id', Auth::id())
            ->with(['product.images', 'product.variants'])
            ->orderBy('id')
            ->get();
    }

    /**
     * FR-30: move-to-cart picks the first SKU of the product that still has
     * stock, merges into an existing row (FR-26), then drops the save.
     */
    public function moveToCart(int $wishlistId): void
    {
        $row = WishlistModel::query()
            ->where('user_id', Auth::id())
            ->with('product')
            ->find($wishlistId);

        if ($row === null || Auth::id() === null) {
            return;
        }

        $product = $row->product;

        if (! $product->is_active) {
            $this->notice = $product->name.' is no longer on sale; remove it from the wishlist.';

            return;
        }

        $variant = $product->variants
            ->first(fn (ProductVariant $variant): bool => $variant->stock > 0);

        if ($variant === null) {
            $this->notice = 'No size or color of '.$product->name.' is in stock right now.';

            return;
        }

        CartItem::mergeAdd((int) Auth::id(), $variant);

        $row->delete();

        $this->notice = $product->name.' ('.$variant->label().') moved into the cart.';
    }

    /**
     * FR-31: saving a product stays until the account removes it.
     */
    public function remove(int $wishlistId): void
    {
        $row = WishlistModel::query()
            ->where('user_id', Auth::id())
            ->find($wishlistId);

        if ($row === null) {
            return;
        }

        $row->delete();

        $this->notice = 'Removed from the wishlist.';
    }

    public function render(): View
    {
        return view('livewire.wishlist');
    }
}
