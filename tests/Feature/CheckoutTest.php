<?php

use App\Livewire\CheckoutForm;
use App\Livewire\Orders\Show;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Livewire\Livewire;

/**
 * Puts one SKU line into the customer's cart.
 */
function checkoutCartLine(int $userId, ProductVariant $variant, int $quantity = 1): CartItem
{
    return CartItem::query()->create([
        'user_id' => $userId,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);
}

test('guests are sent to login before checkout (FR-32)', function () {
    $this->get('/checkout')->assertRedirect(route('login'));
});

test('an empty cart shows catalog guidance instead of the form (FR-32)', function () {
    Livewire::actingAs(customer())->test(CheckoutForm::class)
        ->assertSee('Your cart is empty')
        ->assertSee(route('home'))
        ->assertDontSee('Place order');
});

test('the summary adds the flat courier fee to the subtotal (FR-35, FR-36)', function () {
    $product = productWithVariants(['base_price' => 150000], [
        ['sku' => 'SKU-CHK-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();

    checkoutCartLine($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->assertSee('Rp 150.000')
        ->set('shippingMethod', 'courier')
        ->assertSee('Rp 20.000')
        ->assertSee('Rp 170.000');
});

test('the courier fee follows the store configuration (FR-35)', function () {
    config(['clothested.shipping_fee' => 25000]);

    $product = productWithVariants(['base_price' => 150000], [
        ['sku' => 'SKU-CHK-9', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();

    checkoutCartLine($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'courier')
        ->assertSee('Rp 175.000');
});

test('an incomplete courier address blocks the order with field markers (FR-34)', function () {
    $product = productWithVariants(['base_price' => 150000], [
        ['sku' => 'SKU-CHK-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $variant = $product->variants->first();
    $customer = customer();

    checkoutCartLine($customer->id, $variant);

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'courier')
        ->set('recipientName', '')
        ->set('phone', '')
        ->set('addressLine', '')
        ->set('city', '')
        ->set('postalCode', '')
        ->call('placeOrder')
        ->assertHasErrors(['recipientName', 'phone', 'addressLine', 'city', 'postalCode']);

    expect(Order::query()->count())->toBe(0)
        ->and((int) $variant->refresh()->stock)->toBe(5)
        ->and(CartItem::query()->count())->toBe(1);
});

test('the phone number must pass the format check (FR-34)', function () {
    $product = productWithVariants(['base_price' => 150000], [
        ['sku' => 'SKU-CHK-3', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();

    checkoutCartLine($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'courier')
        ->set('recipientName', 'Ayu Lestari')
        ->set('phone', 'call me maybe')
        ->set('addressLine', 'Jl. Merdeka 10')
        ->set('city', 'Bandung')
        ->set('postalCode', '40111')
        ->call('placeOrder')
        ->assertHasErrors(['phone' => 'regex']);

    expect(Order::query()->count())->toBe(0);
});

test('a courier order stores the code, snapshots, and reduces stock by quantity (FR-33, FR-34, FR-36, FR-37)', function () {
    $product = productWithVariants(['name' => 'Flow Dress', 'base_price' => 150000], [
        ['sku' => 'SKU-CHK-4', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 8],
    ]);
    $variant = $product->variants->first();
    $customer = customer();

    checkoutCartLine($customer->id, $variant, 2);

    $component = Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'courier')
        ->set('recipientName', 'Ayu Lestari')
        ->set('phone', '081234567890')
        ->set('addressLine', 'Jl. Merdeka 10')
        ->set('city', 'Bandung')
        ->set('postalCode', '40111')
        ->set('note', 'Please fold the dress.')
        ->call('placeOrder');

    $order = Order::query()->firstOrFail();

    $component->assertRedirect(route('orders.show', $order->order_code));

    expect($order->order_code)->toMatch('/^TRX-\d{8}-\d{4}$/')
        ->and($order->status)->toBe('pending')
        ->and($order->shipping_method)->toBe('courier')
        ->and((float) $order->subtotal)->toBe(300000.0)
        ->and((float) $order->shipping_fee)->toBe(20000.0)
        ->and((float) $order->total)->toBe(320000.0)
        ->and($order->note)->toBe('Please fold the dress.')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->product_name)->toBe('Flow Dress')
        ->and($order->items->first()->variant_label)->toBe('Navy / M')
        ->and((float) $order->items->first()->unit_price)->toBe(150000.0)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and((float) $order->items->first()->line_total)->toBe(300000.0)
        ->and($order->shippingAddress?->recipient_name)->toBe('Ayu Lestari')
        ->and((int) $variant->refresh()->stock)->toBe(6)
        ->and(CartItem::query()->count())->toBe(0);
});

test('pickup skips the address and the fee (FR-33, FR-35)', function () {
    $product = productWithVariants(['base_price' => 150000], [
        ['sku' => 'SKU-CHK-5', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();

    checkoutCartLine($customer->id, $product->variants->first());

    $component = Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'pickup')
        ->set('note', 'I will pick up after five.')
        ->call('placeOrder');

    $order = Order::query()->firstOrFail();

    $component->assertRedirect(route('orders.show', $order->order_code));

    Livewire::actingAs($customer)->test(Show::class, ['orderCode' => $order->order_code])
        ->assertSee('In-store pickup')
        ->assertSee('No address needed');

    expect($order->status)->toBe('pending')
        ->and($order->shipping_method)->toBe('pickup')
        ->and((float) $order->shipping_fee)->toBe(0.0)
        ->and((float) $order->total)->toBe(150000.0)
        ->and($order->shippingAddress)->toBeNull();
});

test('a cart whose stock changed cannot become an order (FR-29, FR-37)', function () {
    $healthy = productWithVariants(['name' => 'Steady Tee', 'base_price' => 90000], [
        ['sku' => 'SKU-CHK-6', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $scarce = productWithVariants(['name' => 'Scarce Tee', 'base_price' => 80000], [
        ['sku' => 'SKU-CHK-7', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
    ]);
    $customer = customer();

    checkoutCartLine($customer->id, $healthy->variants->first(), 2);
    checkoutCartLine($customer->id, $scarce->variants->first(), 4);

    $scarce->variants->first()->update(['stock' => 3]);

    Livewire::actingAs($customer)->test(CheckoutForm::class)
        ->set('shippingMethod', 'courier')
        ->set('recipientName', 'Ayu Lestari')
        ->set('phone', '081234567890')
        ->set('addressLine', 'Jl. Merdeka 10')
        ->set('city', 'Bandung')
        ->set('postalCode', '40111')
        ->assertSee('only 3 left')
        ->call('placeOrder')
        ->assertHasErrors('cart');

    expect(Order::query()->count())->toBe(0)
        ->and((int) $healthy->variants->first()->refresh()->stock)->toBe(5)
        ->and((int) $scarce->variants->first()->refresh()->stock)->toBe(3)
        ->and(CartItem::query()->count())->toBe(2);
});

test('order codes stay unique and follow the TRX format (FR-37)', function () {
    $firstProduct = productWithVariants([], [
        ['sku' => 'SKU-CHK-8', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 3],
    ]);
    $secondProduct = productWithVariants([], [
        ['sku' => 'SKU-CHK-10', 'size' => 'L', 'color' => 'Rose', 'additional_price' => 0, 'stock' => 3],
    ]);
    $customer = customer();
    $service = app(OrderService::class);

    checkoutCartLine($customer->id, $firstProduct->variants->first());
    $first = $service->placeOrder($customer, ['shipping_method' => 'pickup', 'note' => null, 'address' => null]);

    checkoutCartLine($customer->id, $secondProduct->variants->first());
    $second = $service->placeOrder($customer, ['shipping_method' => 'pickup', 'note' => null, 'address' => null]);

    expect($first->order_code)->toMatch('/^TRX-\d{8}-\d{4}$/')
        ->and($second->order_code)->toMatch('/^TRX-\d{8}-\d{4}$/')
        ->and($first->order_code)->not->toBe($second->order_code)
        ->and((int) $firstProduct->variants->first()->refresh()->stock)->toBe(2)
        ->and((int) $secondProduct->variants->first()->refresh()->stock)->toBe(2);
});
