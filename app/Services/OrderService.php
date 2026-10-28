<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Class 3.1: OrderService owns the checkout transaction and the stock ledger.
 */
class OrderService
{
    /**
     * FR-37: place a pending order, snapshot its lines, reserve stock, and
     * clear the cart inside one database transaction. Validation failures
     * roll the whole step back, so a stale cart leaves no partial order.
     *
     * @param  array{shipping_method: string, note: string|null, address: array{recipient_name: string, phone: string, address_line: string, city: string, postal_code: string}|null}  $checkout
     */
    public function placeOrder(User $user, array $checkout): Order
    {
        return DB::transaction(function () use ($user, $checkout): Order {
            $cartItems = CartItem::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $variants = ProductVariant::query()
                ->whereIn('id', $cartItems->pluck('product_variant_id'))
                ->with('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->ensureStockIsAvailable($cartItems, $variants);

            $subtotal = 0.0;
            $lines = [];

            foreach ($cartItems as $item) {
                $variant = $variants->get($item->product_variant_id);
                $quantity = (int) $item->quantity;
                $unitPrice = $variant->finalPrice();
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label(),
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $shippingFee = $checkout['shipping_method'] === 'courier'
                ? (float) config('clothested.shipping_fee')
                : 0.0;

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_code' => $this->generateOrderCode(),
                'status' => 'pending',
                'shipping_method' => $checkout['shipping_method'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'note' => $checkout['note'],
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
                $variants->get($line['product_variant_id'])
                    ->decrement('stock', $line['quantity']);
            }

            if ($checkout['shipping_method'] === 'courier') {
                $order->shippingAddress()->create($checkout['address']);
            }

            CartItem::query()->where('user_id', $user->id)->delete();

            return $order->refresh();
        });
    }

    /**
     * FR-40: a pending order can be cancelled and every reserved unit returns
     * to the catalog immediately; other statuses keep their stock committed.
     */
    public function cancel(Order $order, ?string $refundNote = null): void
    {
        DB::transaction(function () use ($order, $refundNote): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isCancellable()) {
                throw ValidationException::withMessages([
                    'order' => 'This order can no longer be cancelled.',
                ]);
            }

            $this->releaseReservedStock($locked);
            $locked->update(array_filter([
                'status' => 'cancelled',
                'refund_note' => $refundNote,
            ], fn (?string $value): bool => $value !== null));
        });

        $order->refresh();

        $this->logTransition($order, 'cancelled', $refundNote);
    }

    public function markReadyForPickup(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->shipping_method !== 'pickup' || $locked->status !== 'processing') {
                throw ValidationException::withMessages([
                    'order' => 'Only processing pickup orders can be marked ready for pickup.',
                ]);
            }

            $locked->update(['status' => 'ready_for_pickup']);
        });

        $this->logTransition($order, 'ready_for_pickup');
    }

    public function markShipped(Order $order, string $waybillNumber): void
    {
        $waybillNumber = trim($waybillNumber);

        if ($waybillNumber === '' || mb_strlen($waybillNumber) > 255) {
            throw ValidationException::withMessages([
                'waybillNumber' => 'Enter a waybill number up to 255 characters.',
            ]);
        }

        DB::transaction(function () use ($order, $waybillNumber): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->shipping_method !== 'courier' || $locked->status !== 'processing') {
                throw ValidationException::withMessages([
                    'order' => 'Only processing courier orders can be marked shipped.',
                ]);
            }

            $address = ShippingAddress::query()
                ->where('order_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if ($address === null) {
                throw ValidationException::withMessages([
                    'order' => 'A courier address is required before shipping this order.',
                ]);
            }

            $address->update(['waybill_number' => trim($waybillNumber)]);
            $locked->update(['status' => 'shipped']);
        });

        $this->logTransition($order, 'shipped');
    }

    public function confirmReceipt(Order $order, User $customer): void
    {
        DB::transaction(function () use ($order, $customer): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (
                $locked->user_id !== $customer->id
                || ! (
                    ($locked->shipping_method === 'courier' && $locked->status === 'shipped')
                    || ($locked->shipping_method === 'pickup' && $locked->status === 'ready_for_pickup')
                )
            ) {
                throw ValidationException::withMessages([
                    'order' => 'This order is not ready for receipt confirmation.',
                ]);
            }

            $locked->update(['status' => 'completed']);
        });

        $this->logTransition($order, 'completed');
    }

    public function adminCancel(Order $order, string $refundNote): void
    {
        $refundNote = trim($refundNote);

        if ($refundNote === '' || mb_strlen($refundNote) > 1000) {
            throw ValidationException::withMessages([
                'refundNote' => 'Enter a cancellation or refund note up to 1,000 characters.',
            ]);
        }

        DB::transaction(function () use ($order, $refundNote): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($locked->status, ['pending', 'processing', 'ready_for_pickup'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Only pending, processing, or ready-for-pickup orders can be cancelled.',
                ]);
            }

            if ($locked->status === 'pending' && $locked->payment()->where('transaction_status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Confirm cancellation with the payment provider before releasing this order.',
                ]);
            }

            $this->releaseReservedStock($locked);
            $locked->update([
                'status' => 'cancelled',
                'refund_note' => $refundNote,
            ]);
        });

        $this->logTransition($order, 'cancelled', $refundNote);
    }

    /**
     * Non-functional requirement: admin status changes leave one structured
     * log line each, so the store can audit who moved which order when.
     */
    private function logTransition(Order $order, string $action, ?string $refundNote = null): void
    {
        Log::info('An order status was updated.', [
            'order_code' => $order->refresh()->order_code,
            'action' => $action,
            'status' => $order->status,
            'refund_note' => $refundNote,
        ]);
    }

    private function releaseReservedStock(Order $order): void
    {
        $quantities = $order->items()
            ->selectRaw('product_variant_id, SUM(quantity) as quantity')
            ->groupBy('product_variant_id')
            ->pluck('quantity', 'product_variant_id');

        $variants = ProductVariant::query()
            ->whereIn('id', $quantities->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($variants as $variant) {
            $variant->increment('stock', (int) $quantities[$variant->id]);
        }
    }

    /**
     * FR-29: every line must still fit the live stock before anything is written.
     *
     * @param  Collection<int, CartItem>  $cartItems
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function ensureStockIsAvailable(Collection $cartItems, Collection $variants): void
    {
        foreach ($cartItems as $item) {
            $variant = $variants->get($item->product_variant_id);

            if ($variant === null || $variant->stock < (int) $item->quantity) {
                $available = $variant?->stock ?? 0;

                throw ValidationException::withMessages([
                    'cart' => ($variant?->product?->name ?? 'An item')
                        .' ('.($variant?->label() ?? 'unknown size').'): '
                        .($available > 0 ? 'only '.$available.' left' : 'sold out')
                        .'. Lower the quantity and try again.',
                ]);
            }
        }
    }

    /**
     * FR-37: the TRX-YYYYMMDD-XXXX code, unique across every order.
     */
    private function generateOrderCode(): string
    {
        $date = now()->format('Ymd');

        do {
            $code = 'TRX-'.$date.'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_code', $code)->exists());

        return $code;
    }
}
