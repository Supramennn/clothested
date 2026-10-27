<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isCancellable()) {
                throw ValidationException::withMessages([
                    'order' => 'This order can no longer be cancelled.',
                ]);
            }

            $quantities = $locked->items()
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

            $locked->update(['status' => 'cancelled']);
        });

        $order->refresh();
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
