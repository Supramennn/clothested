<?php

namespace App\Livewire;

use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Class 3.4: CartManager (setQty, removeItem).
 * The account-owned cart page; every row points at one SKU.
 */
#[Layout('layouts.app')]
#[Title('Cart · ClothesTed')]
class CartManager extends Component
{
    /**
     * Typed quantity binding per cart row, keyed by CartItem id.
     *
     * @var array<int, int|string>
     */
    public array $quantities = [];

    public ?string $notice = null;

    /**
     * Seed the quantity fields from the persisted rows (FR-31).
     */
    public function mount(): void
    {
        foreach ($this->cartItems() as $item) {
            $this->quantities[$item->id] = (int) $item->quantity;
        }
    }

    /**
     * FR-25/FR-31: the signed-in account cart, oldest row first, with every
     * SKU priced against its live product data.
     *
     * @return Collection<int, CartItem>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        return CartItem::query()
            ->where('user_id', Auth::id())
            ->with(['variant.product.images'])
            ->orderBy('id')
            ->get();
    }

    /**
     * FR-28: the running subotal re-rendered from the live rows.
     */
    #[Computed]
    public function subtotal(): float
    {
        return (float) $this->cartItems()->sum(
            fn (CartItem $item): float => $item->quantity * $item->variant->finalPrice()
        );
    }

    /**
     * Typed inputs arrive per row through the binding and then run through
     * the same clamp as direct edits (FR-27).
     */
    public function updatedQuantities(mixed $value, string $key): void
    {
        $itemId = (int) str($key)->afterLast('.');

        $this->setQty($itemId, $value);
    }

    /**
     * FR-27: clamp the quantity to 1..30 and the live stock, then say how
     * far the input slipped.
     */
    public function setQty(int $itemId, int|string $quantity): void
    {
        $row = CartItem::query()
            ->where('user_id', Auth::id())
            ->with('variant')
            ->find($itemId);

        if ($row === null) {
            return;
        }

        $stock = (int) $row->variant->stock;

        if ($stock < 1) {
            $this->notice = $row->variant->label().' is out of stock; remove it or pick another size or color.';

            $this->quantities[$row->id] = (int) $row->quantity;

            return;
        }

        $requested = (int) max(1, min(30, (int) $quantity));

        $finalQuantity = min($requested, $stock);

        if ($finalQuantity !== (int) $row->quantity) {
            $row->update(['quantity' => $finalQuantity]);
        }

        $this->quantities[$row->id] = $finalQuantity;

        if ($stock < $requested) {
            $this->notice = 'Quantity clipped to '.$finalQuantity.': only '.$stock.' left in stock.';
        } elseif ($requested < (int) $quantity) {
            $this->notice = 'Quantity clipped to '.$finalQuantity.', the per-SKU limit is 30.';
        } else {
            $this->notice = null;
        }
    }

    /**
     * FR-28: remove a row immediately; the list and subtotals refresh.
     */
    public function removeItem(int $itemId): void
    {
        $row = CartItem::query()
            ->where('user_id', Auth::id())
            ->find($itemId);

        if ($row === null) {
            return;
        }

        $row->delete();

        unset($this->quantities[$row->id]);

        $this->notice = 'Item removed from the cart.';
    }

    public function render(): View
    {
        return view('livewire.cart-manager');
    }
}
