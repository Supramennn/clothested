<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-39/FR-40: one order of the signed-in customer, with cancellation while
 * the order is still pending.
 */
#[Layout('layouts.app')]
#[Title('Order — ClothesTed')]
class Show extends Component
{
    public string $orderCode = '';

    public ?string $notice = null;

    /**
     * Load the order or stop early; another account's code is a plain 404.
     */
    public function mount(string $orderCode): void
    {
        $this->orderCode = $orderCode;

        $this->order();
    }

    /**
     * FR-39: scoped to the current account on every read.
     */
    #[Computed]
    public function order(): Order
    {
        return Order::query()
            ->where('user_id', Auth::id())
            ->where('order_code', $this->orderCode)
            ->with(['items.variant.product.images', 'shippingAddress', 'payment'])
            ->firstOr(fn () => abort(404));
    }

    /**
     * FR-40: cancel only while pending; the service returns the stock.
     */
    public function cancel(OrderService $orders): void
    {
        if (! $this->order->isCancellable()) {
            $this->notice = 'This order can no longer be cancelled.';

            return;
        }

        $orders->cancel($this->order);

        unset($this->order);

        $this->notice = 'Order cancelled. The reserved stock is back in the catalog.';
    }

    public function render(): View
    {
        return view('livewire.orders.show', ['order' => $this->order]);
    }
}
