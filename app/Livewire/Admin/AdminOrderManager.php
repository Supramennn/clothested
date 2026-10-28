<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\MidtransException;
use App\Services\MidtransPaymentService;
use App\Services\OrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Order manager | ClothesTed')]
class AdminOrderManager extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = 'open';

    public ?string $orderCode = null;

    public string $waybillNumber = '';

    public string $refundNote = '';

    public ?string $notice = null;

    public function mount(?string $orderCode = null): void
    {
        $this->orderCode = $orderCode;

        if ($orderCode !== null) {
            $order = $this->selectedOrder();
            if ($order === null) {
                abort(404);
            }

            $this->waybillNumber = $order->shippingAddress?->waybill_number ?? '';
            $this->refundNote = $order->refund_note ?? '';
        }
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'payment'])
            ->withCount('items')
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereNotIn('status', ['completed', 'cancelled']))
            ->when(
                in_array($this->statusFilter, ['pending', 'processing', 'shipped', 'ready_for_pickup', 'completed', 'cancelled'], true),
                fn ($query) => $query->where('status', $this->statusFilter)
            )
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function selectedOrder(): ?Order
    {
        if ($this->orderCode === null) {
            return null;
        }

        return Order::query()
            ->with(['user', 'items.variant.product', 'shippingAddress', 'payment'])
            ->where('order_code', $this->orderCode)
            ->firstOr(fn () => abort(404));
    }

    public function updatedStatusFilter(): void
    {
        if (! in_array($this->statusFilter, ['open', 'all', 'pending', 'processing', 'shipped', 'ready_for_pickup', 'completed', 'cancelled'], true)) {
            $this->statusFilter = 'open';
        }

        $this->resetPage();
    }

    public function markReadyForPickup(OrderService $orders): void
    {
        $orders->markReadyForPickup($this->requireSelectedOrder());
        $this->refreshSelectedOrder('Order is ready for pickup.');
    }

    public function markShipped(OrderService $orders): void
    {
        $this->waybillNumber = trim($this->waybillNumber);
        $this->validate([
            'waybillNumber' => ['required', 'string', 'max:255'],
        ]);

        $orders->markShipped($this->requireSelectedOrder(), $this->waybillNumber);
        $this->refreshSelectedOrder('Waybill saved. The order is marked shipped.');
    }

    public function cancelOrder(OrderService $orders, MidtransPaymentService $payments): void
    {
        $this->refundNote = trim($this->refundNote);
        $this->validate([
            'refundNote' => ['required', 'string', 'max:1000'],
        ]);

        $order = $this->requireSelectedOrder();

        if ($order->status === 'pending') {
            try {
                $payments->cancelPendingOrder($order, trim($this->refundNote));
            } catch (MidtransException $exception) {
                $this->addError('order', $exception->getMessage());

                return;
            }
        } else {
            $orders->adminCancel($order, trim($this->refundNote));
        }

        $this->refreshSelectedOrder('Order cancelled. Reserved stock is back in the catalog.');
    }

    private function requireSelectedOrder(): Order
    {
        return $this->selectedOrder
            ?? throw ValidationException::withMessages(['order' => 'Select an order before changing its status.']);
    }

    private function refreshSelectedOrder(string $notice): void
    {
        unset($this->selectedOrder);
        $this->notice = $notice;
    }

    public function render(): View
    {
        return view('livewire.admin.admin-order-manager');
    }
}
