<?php

use App\Livewire\Admin\AdminOrderManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Orders\Show as CustomerOrderShow;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function adminOrderWithLine(Order $order, ProductVariant $variant, int $quantity = 1): Order
{
    $unitPrice = $variant->finalPrice();

    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => $variant->product->name,
        'variant_label' => $variant->label(),
        'unit_price' => $unitPrice,
        'quantity' => $quantity,
        'line_total' => $unitPrice * $quantity,
    ]);

    return $order->refresh();
}

function adminPaymentFor(Order $order, array $attributes = []): Payment
{
    return Payment::query()->create(array_merge([
        'order_id' => $order->id,
        'midtrans_order_id' => $order->order_code,
        'gross_amount' => $order->total,
        'transaction_status' => 'settlement',
        'paid_at' => now(),
    ], $attributes));
}

test('dashboard shows settled sales and the current order queue', function () {
    $this->travelTo('2026-10-27 12:00:00');
    $admin = User::factory()->admin()->create();
    $paid = Order::factory()->for(customer())->processing()->create([
        'total' => 100000,
        'placed_at' => now()->subDays(2),
    ]);
    adminPaymentFor($paid);
    $settledYesterday = Order::factory()->for(customer())->processing()->create([
        'total' => 40000,
        'placed_at' => now(),
    ]);
    adminPaymentFor($settledYesterday, ['paid_at' => now()->subDay()]);
    $cancelledPaid = Order::factory()->for(customer())->cancelled()->create([
        'total' => 25000,
        'placed_at' => now(),
    ]);
    adminPaymentFor($cancelledPaid);
    $open = Order::factory()->for(customer())->create([
        'order_code' => 'TRX-QUEUE-0001',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)->test(Dashboard::class)
        ->assertSee('Rp 100.000')
        ->assertSee('TRX-QUEUE-0001')
        ->assertSee('Orders awaiting action');
});

test('admins add a waybill before shipping and customers confirm delivery', function () {
    $admin = User::factory()->admin()->create();
    $customer = customer();
    $order = Order::factory()->for($customer)->courier()->processing()->create([
        'order_code' => 'TRX-SHIP-0001',
    ]);
    $order->shippingAddress()->create([
        'recipient_name' => $customer->name,
        'phone' => '081234567890',
        'address_line' => 'Jl. Merdeka 10',
        'city' => 'Bandung',
        'postal_code' => '40111',
    ]);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->call('markShipped')
        ->assertHasErrors(['waybillNumber' => 'required']);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->set('waybillNumber', '  WB-CLT-889 ')
        ->call('markShipped')
        ->assertHasNoErrors();

    expect($order->refresh()->status)->toBe('shipped')
        ->and($order->shippingAddress->refresh()->waybill_number)->toBe('WB-CLT-889');

    Livewire::actingAs($customer)->test(CustomerOrderShow::class, ['orderCode' => $order->order_code])
        ->assertSee('WB-CLT-889')
        ->call('confirmReceipt')
        ->assertSee('Receipt confirmed. This order is complete.');

    expect($order->refresh()->status)->toBe('completed');
});

test('shipping rejects an empty waybill in the order service', function () {
    $customer = customer();
    $order = Order::factory()->for($customer)->courier()->processing()->create();
    $order->shippingAddress()->create([
        'recipient_name' => $customer->name,
        'phone' => '081234567890',
        'address_line' => 'Jl. Merdeka 10',
        'city' => 'Bandung',
        'postal_code' => '40111',
    ]);

    expect(fn () => app(OrderService::class)->markShipped($order, '  '))
        ->toThrow(ValidationException::class);

    expect($order->refresh()->status)->toBe('processing')
        ->and($order->shippingAddress->refresh()->waybill_number)->toBeNull();
});

test('pickup orders become complete only after customer receipt confirmation', function () {
    $admin = User::factory()->admin()->create();
    $customer = customer();
    $order = Order::factory()->for($customer)->processing()->create([
        'shipping_method' => 'pickup',
        'shipping_fee' => 0,
        'status' => 'processing',
    ]);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->call('markReadyForPickup')
        ->assertHasNoErrors();

    expect($order->refresh()->status)->toBe('ready_for_pickup');

    Livewire::actingAs($customer)->test(CustomerOrderShow::class, ['orderCode' => $order->order_code])
        ->call('confirmReceipt')
        ->assertSee('Receipt confirmed. This order is complete.');

    expect($order->refresh()->status)->toBe('completed');
});

test('admin cancellation returns reserved stock and stores the manual refund note', function () {
    $admin = User::factory()->admin()->create();
    $customer = customer();
    $product = productWithVariants(['base_price' => 50000], [
        ['sku' => 'SKU-ADMIN-CANCEL', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 3],
    ]);
    $variant = $product->variants->firstOrFail();
    $order = Order::factory()->for($customer)->processing()->create([
        'order_code' => 'TRX-CANCEL-0001',
    ]);
    adminOrderWithLine($order, $variant, 2);
    adminPaymentFor($order);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->set('refundNote', 'Refund processed manually through Midtrans.')
        ->call('cancelOrder')
        ->assertHasNoErrors()
        ->assertSee('Reserved stock is back in the catalog.');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->refund_note)->toBe('Refund processed manually through Midtrans.')
        ->and($variant->refresh()->stock)->toBe(5);
});

test('shipped orders cannot be cancelled or returned to stock', function () {
    $admin = User::factory()->admin()->create();
    $customer = customer();
    $product = productWithVariants(['base_price' => 50000], [
        ['sku' => 'SKU-SHIPPED-LOCK', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 2],
    ]);
    $variant = $product->variants->firstOrFail();
    $order = Order::factory()->for($customer)->courier()->create([
        'order_code' => 'TRX-SHIPPED-LOCK-1',
        'status' => 'shipped',
    ]);
    adminOrderWithLine($order, $variant);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->assertDontSee('Cancel order')
        ->set('refundNote', 'Customer refund handled manually.')
        ->call('cancelOrder')
        ->assertHasErrors('order');

    expect($order->refresh()->status)->toBe('shipped')
        ->and($variant->refresh()->stock)->toBe(2);
});

test('admin cancellation asks Midtrans to close a pending payment before releasing stock', function () {
    config([
        'services.midtrans.server_key' => 'sandbox-server-key',
        'services.midtrans.api_url' => 'https://api.sandbox.midtrans.com',
    ]);

    $admin = User::factory()->admin()->create();
    $customer = customer();
    $product = productWithVariants(['base_price' => 50000], [
        ['sku' => 'SKU-ADMIN-PENDING', 'size' => 'M', 'color' => 'Black', 'additional_price' => 0, 'stock' => 3],
    ]);
    $variant = $product->variants->firstOrFail();
    $order = Order::factory()->for($customer)->create([
        'order_code' => 'TRX-ADMIN-PENDING-1',
        'total' => 50000,
    ]);
    adminOrderWithLine($order, $variant, 1);
    adminPaymentFor($order, ['transaction_status' => 'pending', 'paid_at' => null]);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/TRX-ADMIN-PENDING-1/cancel' => Http::response([], 200),
        'https://api.sandbox.midtrans.com/v2/TRX-ADMIN-PENDING-1/status' => Http::response([
            'order_id' => 'TRX-ADMIN-PENDING-1',
            'gross_amount' => '50000.00',
            'transaction_status' => 'cancel',
            'transaction_id' => 'MID-ADMIN-1',
            'payment_type' => 'bank_transfer',
        ]),
    ]);

    Livewire::actingAs($admin)->test(AdminOrderManager::class, ['orderCode' => $order->order_code])
        ->set('refundNote', 'Payment was cancelled before settlement.')
        ->call('cancelOrder')
        ->assertHasNoErrors();

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->refund_note)->toBe('Payment was cancelled before settlement.')
        ->and($order->payment->transaction_status)->toBe('cancel')
        ->and($variant->refresh()->stock)->toBe(4);
});

test('admin order details show stored Midtrans failure context', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->for(customer())->create([
        'order_code' => 'TRX-PAYMENT-FAIL-1',
    ]);
    adminPaymentFor($order, [
        'transaction_status' => 'deny',
        'raw_payload' => [
            'transaction_status' => 'deny',
            'status_code' => '202',
            'status_message' => 'Payment denied by provider',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order->order_code))
        ->assertOk()
        ->assertSee('Payment did not settle.')
        ->assertSee('Payment denied by provider');
});

test('admin order details explain pending orders without a payment session', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->for(customer())->create([
        'order_code' => 'TRX-PAYMENT-START-FAIL-1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order->order_code))
        ->assertOk()
        ->assertSee('No payment session is recorded.');
});
