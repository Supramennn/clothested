<?php

use App\Livewire\CheckoutForm;
use App\Livewire\Orders\Show;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Places an order and reserves the requested SKU quantity through checkout.
 *
 * @return array{Order, ProductVariant}
 */
function orderWithReservedStock(int $quantity = 2): array
{
    $product = productWithVariants(['name' => 'Payment Dress', 'base_price' => 50000], [
        ['sku' => 'SKU-PAY-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();
    $variant = $product->variants->firstOrFail();

    CartItem::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);

    $order = app(OrderService::class)->placeOrder($customer, [
        'shipping_method' => 'pickup',
        'note' => null,
        'address' => null,
    ]);

    return [$order, $variant];
}

function paymentForOrder(Order $order, array $attributes = []): Payment
{
    return Payment::query()->create(array_merge([
        'order_id' => $order->id,
        'midtrans_order_id' => $order->order_code,
        'gross_amount' => $order->total,
        'transaction_status' => 'pending',
        'expiry_time' => now()->addHours(24),
    ], $attributes));
}

/**
 * @return array<string, mixed>
 */
function signedMidtransNotification(Order $order, string $transactionStatus): array
{
    $notification = [
        'order_id' => $order->order_code,
        'status_code' => '200',
        'gross_amount' => number_format((float) $order->total, 2, '.', ''),
        'signature_key' => '',
        'transaction_status' => $transactionStatus,
    ];

    $notification['signature_key'] = hash(
        'sha512',
        $notification['order_id'].$notification['status_code'].$notification['gross_amount'].config('services.midtrans.server_key')
    );

    return $notification;
}

beforeEach(function () {
    config([
        'services.midtrans.server_key' => 'sandbox-server-key',
        'services.midtrans.api_url' => 'https://api.sandbox.midtrans.com',
        'services.midtrans.snap_url' => 'https://app.sandbox.midtrans.com',
        'clothested.payment_expiry_hours' => 24,
    ]);
});

test('checkout redirects to Snap and sends supported methods, total, and expiry (FR-38, FR-41, FR-42)', function () {
    Http::fake([
        'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
            'token' => 'snap-token',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token',
        ], 201),
    ]);

    $product = productWithVariants(['name' => 'Payment Dress', 'base_price' => 50000], [
        ['sku' => 'SKU-PAY-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();
    CartItem::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->call('placeOrder')
        ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token');

    $order = Order::query()->firstOrFail();
    $payment = $order->payment;

    expect($payment)->not->toBeNull()
        ->and($payment->midtrans_order_id)->toBe($order->order_code)
        ->and((float) $payment->gross_amount)->toBe((float) $order->total)
        ->and($payment->transaction_status)->toBe('pending')
        ->and($payment->expiry_time->greaterThanOrEqualTo(now()->addHours(23)))->toBeTrue();

    Http::assertSent(function (Request $request) use ($order): bool {
        return str_ends_with($request->url(), '/snap/v1/transactions')
            && $request['transaction_details']['order_id'] === $order->order_code
            && $request['transaction_details']['gross_amount'] === (int) $order->total
            && $request['enabled_payments'] === ['bank_transfer', 'gopay', 'qris', 'credit_card']
            && $request['expiry']['unit'] === 'hours'
            && $request['expiry']['duration'] === 24;
    });
});

test('a valid settlement notification is reconfirmed and idempotent (FR-44, FR-45, FR-47)', function () {
    [$order, $variant] = orderWithReservedStock();
    $payment = paymentForOrder($order);
    $confirmed = [
        'order_id' => $payment->midtrans_order_id,
        'gross_amount' => number_format((float) $order->total, 2, '.', ''),
        'transaction_status' => 'settlement',
        'transaction_id' => 'midtrans-transaction-1',
        'payment_type' => 'bank_transfer',
    ];
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::response($confirmed),
    ]);
    $notification = signedMidtransNotification($order, 'settlement');

    $this->postJson('/api/midtrans/callback', $notification)->assertOk();
    $paidAt = $payment->refresh()->paid_at;
    $this->postJson('/api/midtrans/callback', $notification)->assertOk();

    expect($order->refresh()->status)->toBe('processing')
        ->and($payment->refresh()->transaction_status)->toBe('settlement')
        ->and($payment->paid_at->equalTo($paidAt))->toBeTrue()
        ->and($payment->midtrans_transaction_id)->toBe('midtrans-transaction-1')
        ->and($payment->payment_type)->toBe('bank_transfer')
        ->and($payment->raw_payload['transaction_status'])->toBe('settlement')
        ->and((int) $variant->refresh()->stock)->toBe(3);

    Http::assertSentCount(2);
});

test('invalid signatures return HTTP 200 without changing order or stock (FR-44)', function () {
    [$order, $variant] = orderWithReservedStock();
    $payment = paymentForOrder($order);
    $notification = signedMidtransNotification($order, 'settlement');
    $notification['signature_key'] = 'invalid';
    Http::fake();

    $this->postJson('/api/midtrans/callback', $notification)->assertOk();

    expect($order->refresh()->status)->toBe('pending')
        ->and($payment->refresh()->transaction_status)->toBe('pending')
        ->and($payment->raw_payload)->toBeNull()
        ->and((int) $variant->refresh()->stock)->toBe(3);

    Http::assertNothingSent();
});

test('a denied payment cancels the order and restores stock only once (FR-46)', function () {
    [$order, $variant] = orderWithReservedStock();
    $payment = paymentForOrder($order);
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => number_format((float) $order->total, 2, '.', ''),
            'transaction_status' => 'deny',
            'transaction_id' => 'midtrans-transaction-2',
            'payment_type' => 'credit_card',
        ]),
    ]);
    $notification = signedMidtransNotification($order, 'deny');

    $this->postJson('/api/midtrans/callback', $notification)->assertOk();
    $this->postJson('/api/midtrans/callback', $notification)->assertOk();

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($payment->refresh()->transaction_status)->toBe('deny')
        ->and($payment->payment_type)->toBe('card')
        ->and((int) $variant->refresh()->stock)->toBe(5);
});

test('checking payment status requests the server-side status and updates the order (FR-43, FR-45)', function () {
    [$order] = orderWithReservedStock();
    $payment = paymentForOrder($order);
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => number_format((float) $order->total, 2, '.', ''),
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
            'transaction_id' => 'midtrans-transaction-5',
            'payment_type' => 'credit_card',
        ]),
    ]);

    Livewire::actingAs($order->user)->test(Show::class, ['orderCode' => $order->order_code])
        ->call('checkPaymentStatus')
        ->assertSee('Payment status: Settlement.');

    expect($order->refresh()->status)->toBe('processing')
        ->and($payment->refresh()->transaction_status)->toBe('settlement')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->payment_type)->toBe('card');
});

test('a customer cancellation cancels an open Midtrans transaction before releasing stock', function () {
    [$order, $variant] = orderWithReservedStock();
    $payment = paymentForOrder($order);
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/*/cancel' => Http::response(['transaction_status' => 'cancel']),
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => number_format((float) $order->total, 2, '.', ''),
            'transaction_status' => 'cancel',
            'transaction_id' => 'midtrans-transaction-3',
            'payment_type' => 'gopay',
        ]),
    ]);

    Livewire::actingAs($order->user)->test(Show::class, ['orderCode' => $order->order_code])
        ->call('cancel')
        ->assertSee('Order cancelled. The reserved stock is back in the catalog.');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($payment->refresh()->transaction_status)->toBe('cancel')
        ->and((int) $variant->refresh()->stock)->toBe(5);

    Http::assertSentCount(2);
});

test('the scheduled expiry releases stock after Midtrans confirms expiration (FR-42, FR-46)', function () {
    [$order, $variant] = orderWithReservedStock();
    $payment = paymentForOrder($order, ['expiry_time' => now()->subMinute()]);
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/*/expire' => Http::response(['transaction_status' => 'expire']),
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => number_format((float) $order->total, 2, '.', ''),
            'transaction_status' => 'expire',
            'transaction_id' => 'midtrans-transaction-4',
            'payment_type' => 'qris',
        ]),
    ]);

    $this->artisan('midtrans:expire-pending')->assertSuccessful();

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($payment->refresh()->transaction_status)->toBe('expire')
        ->and((int) $variant->refresh()->stock)->toBe(5);
});

test('the scheduled expiry releases stock for an old order whose Snap request failed', function () {
    [$order, $variant] = orderWithReservedStock();
    $order->update(['placed_at' => now()->subHours(25)]);
    Http::fake();

    $this->artisan('midtrans:expire-pending')->assertSuccessful();

    expect($order->refresh()->status)->toBe('cancelled')
        ->and((int) $variant->refresh()->stock)->toBe(5);

    Http::assertNothingSent();
});
