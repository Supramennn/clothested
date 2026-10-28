<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\MidtransException;
use App\Services\MidtransPaymentService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-39/FR-40: one order of the signed-in customer, with cancellation while
 * the order is still pending.
 */
#[Layout('layouts.app')]
#[Title('Order · ClothesTed')]
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
    public function cancel(MidtransPaymentService $payments): void
    {
        if (! $this->order->isCancellable()) {
            $this->notice = 'This order can no longer be cancelled.';

            return;
        }

        try {
            $payments->cancelPendingOrder($this->order);
        } catch (MidtransException $exception) {
            $this->notice = $exception->getMessage();

            return;
        } catch (ValidationException $exception) {
            $this->notice = $exception->errors()['order'][0] ?? 'This order can no longer be cancelled.';

            return;
        }

        unset($this->order);

        $this->notice = 'Order cancelled. The reserved stock is back in the catalog.';
    }

    public function startPayment(MidtransPaymentService $payments): void
    {
        if (! $this->order->isCancellable() || $this->order->payment !== null) {
            $this->notice = 'This order already has a payment or can no longer be paid.';

            return;
        }

        try {
            $snapUrl = $payments->createSnapTransaction($this->order);
        } catch (MidtransException $exception) {
            $this->notice = $exception->getMessage();

            return;
        } catch (ValidationException $exception) {
            $this->notice = $exception->errors()['payment'][0] ?? 'This order can no longer start a payment.';

            return;
        }

        $this->redirect($snapUrl, navigate: false);
    }

    public function checkPaymentStatus(MidtransPaymentService $payments): void
    {
        $payment = $this->order->payment;

        if (! $payment instanceof Payment) {
            $this->notice = 'No payment session has been started for this order.';

            return;
        }

        try {
            $payment = $payments->refreshPayment($payment);
        } catch (MidtransException $exception) {
            $this->notice = $exception->getMessage();

            return;
        }

        unset($this->order);
        $this->notice = 'Payment status: '.str_replace('_', ' ', ucfirst($payment->transaction_status)).'.';
    }

    public function confirmReceipt(OrderService $orders): void
    {
        $customer = Auth::user();

        if (! $customer instanceof User) {
            abort(403);
        }

        try {
            $orders->confirmReceipt($this->order, $customer);
        } catch (ValidationException $exception) {
            $this->notice = $exception->errors()['order'][0] ?? 'This order is not ready for receipt confirmation.';

            return;
        }

        unset($this->order);
        $this->notice = 'Receipt confirmed. This order is complete.';
    }

    public function render(): View
    {
        return view('livewire.orders.show', ['order' => $this->order]);
    }
}
