<?php

use App\Livewire\Orders\Index;
use App\Livewire\Orders\Show;
use App\Models\Order;
use App\Models\ProductVariant;
use Livewire\Livewire;

/**
 * Builds a pending order with one snapshot line for the given SKU.
 */
function orderWithLine(Order $order, ProductVariant $variant, int $quantity = 2): Order
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

test('guests are sent to login before seeing orders (FR-39)', function () {
    $order = Order::factory()->create();

    $this->get('/orders')->assertRedirect(route('login'));
    $this->get(route('orders.show', $order->order_code))->assertRedirect(route('login'));
});

test('the order list only shows the signed-in customer orders (FR-39)', function () {
    $customer = customer();
    $other = customer();

    $ownOrder = Order::factory()->for($customer)->create(['order_code' => 'TRX-20261026-0001']);
    $foreignOrder = Order::factory()->for($other)->create(['order_code' => 'TRX-20261026-0002']);

    Livewire::actingAs($customer)->test(Index::class)
        ->assertSee($ownOrder->order_code)
        ->assertDontSee($foreignOrder->order_code);
});

test('an empty history explains how an order appears (FR-39)', function () {
    Livewire::actingAs(customer())->test(Index::class)
        ->assertSee('No orders yet')
        ->assertSee('Browse the catalog');
});

test('the detail page shows the code, snapshot lines, address, and totals (FR-39)', function () {
    $product = productWithVariants(['name' => 'Flow Dress', 'base_price' => 150000], [
        ['sku' => 'SKU-ORD-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
    ]);
    $variant = $product->variants->first();
    $customer = customer();

    $order = Order::factory()->for($customer)->courier()->create([
        'order_code' => 'TRX-20261026-0101',
        'subtotal' => 150000,
        'shipping_fee' => 20000,
        'total' => 170000,
        'note' => 'Please fold the dress.',
    ]);

    orderWithLine($order, $variant, 1);

    $order->shippingAddress()->create([
        'recipient_name' => 'Ayu Lestari',
        'phone' => '081234567890',
        'address_line' => 'Jl. Merdeka 10',
        'city' => 'Bandung',
        'postal_code' => '40111',
    ]);

    Livewire::actingAs($customer)->test(Show::class, ['orderCode' => $order->order_code])
        ->assertSee('TRX-20261026-0101')
        ->assertSee('Flow Dress')
        ->assertSee('Navy / M')
        ->assertSee('Ayu Lestari')
        ->assertSee('Jl. Merdeka 10')
        ->assertSee('Bandung 40111')
        ->assertSee('Please fold the dress.')
        ->assertSee('Rp 150.000')
        ->assertSee('Rp 20.000')
        ->assertSee('Rp 170.000')
        ->assertSee('Cancel order');
});

test('another account cannot open the order detail (FR-39)', function () {
    $owner = customer();
    $intruder = customer();
    $order = Order::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('orders.show', $order->order_code))
        ->assertNotFound();
});

test('cancelling a pending order returns its stock and hides the action (FR-40)', function () {
    $product = productWithVariants(['name' => 'Flow Dress', 'base_price' => 150000], [
        ['sku' => 'SKU-ORD-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $variant = $product->variants->first();
    $customer = customer();
    $order = Order::factory()->for($customer)->create(['order_code' => 'TRX-20261026-0102']);

    orderWithLine($order, $variant, 2);

    Livewire::actingAs($customer)->test(Show::class, ['orderCode' => $order->order_code])
        ->call('cancel')
        ->assertSee('Order cancelled. The reserved stock is back in the catalog.')
        ->assertDontSee('Cancel this order')
        ->call('cancel')
        ->assertSee('This order can no longer be cancelled.');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and((int) $variant->refresh()->stock)->toBe(7);
});

test('a processing order refuses cancellation and keeps the stock (FR-40)', function () {
    $product = productWithVariants(['name' => 'Flow Dress', 'base_price' => 150000], [
        ['sku' => 'SKU-ORD-3', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $variant = $product->variants->first();
    $customer = customer();
    $order = Order::factory()->for($customer)->processing()->create(['order_code' => 'TRX-20261026-0103']);

    orderWithLine($order, $variant, 2);

    Livewire::actingAs($customer)->test(Show::class, ['orderCode' => $order->order_code])
        ->assertDontSee('Cancel this order')
        ->call('cancel')
        ->assertSee('This order can no longer be cancelled.');

    expect($order->refresh()->status)->toBe('processing')
        ->and((int) $variant->refresh()->stock)->toBe(5);
});
