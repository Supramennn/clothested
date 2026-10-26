<?php

use App\Livewire\Auth\Login;
use App\Livewire\CartManager;
use App\Livewire\ProductDetail;
use App\Models\CartItem;
use App\Models\User;
use Livewire\Livewire;

test('guests are sent to login before seeing the cart (FR-25)', function () {
    $this->get('/cart')->assertRedirect(route('login'));
});

test('adding the same SKU twice keeps one row with a combined quantity (FR-26)', function () {
    $product = productWithVariants();
    $customer = customer();

    Livewire::actingAs($customer)->test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart')
        ->call('addToCart');

    expect(CartItem::query()->count())->toBe(1)
        ->and((int) CartItem::query()->first()->quantity)->toBe(2);
});

test('quantity 40 with stock 12 is clipped to 12 with a message (FR-27)', function () {
    $product = productWithVariants([
        'base_price' => 100000,
    ], [
        ['sku' => 'SKU-CART-4', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 12],
    ]);
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CartManager::class)
        ->call('setQty', $row->id, 40)
        ->assertSee('Quantity clipped to 12');

    expect($row->refresh()->quantity)->toBe(12);
});

test('quantity 40 with roomy stock caps at the per-SKU limit of 30 (FR-27)', function () {
    $product = productWithVariants([
        'base_price' => 100000,
    ], [
        ['sku' => 'SKU-CART-5', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 40],
    ]);
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CartManager::class)
        ->call('setQty', $row->id, 40)
        ->assertSee('per-SKU limit is 30');

    expect($row->refresh()->quantity)->toBe(30);
});

test('quantity below one lands on one (FR-27)', function () {
    $product = productWithVariants();
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());
    $row->update(['quantity' => 5]);

    Livewire::actingAs($customer)->test(CartManager::class)
        ->call('setQty', $row->id, 0);

    expect($row->refresh()->quantity)->toBe(1);
});

test('editing a quantity updates the subtotals without asking the server for a page (FR-28)', function () {
    $cheap = productWithVariants(['name' => 'Cheap Tee', 'base_price' => 100000], [
        ['sku' => 'SKU-CART-6', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
    ]);
    $dearer = productWithVariants(['name' => 'Dear Tee', 'base_price' => 200000], [
        ['sku' => 'SKU-CART-7', 'size' => 'L', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
    ]);
    $customer = customer();

    $cheapRow = CartItem::mergeAdd($customer->id, $cheap->variants->first());
    $dearerRow = CartItem::mergeAdd($customer->id, $dearer->variants->first());

    $cart = Livewire::actingAs($customer)->test(CartManager::class);

    expect($cart->instance()->subtotal())->toBe(300000.0);

    $cart->call('setQty', $dearerRow->id, 3);

    expect($dearerRow->refresh()->quantity)->toBe(3)
        ->and($cart->instance()->subtotal())->toBe(700000.0)
        ->and($cart->html())->toContain('Rp 700.000');
});

test('a row whose stock shrank while sitting in the cart is flagged (FR-29)', function () {
    $product = productWithVariants([
        'base_price' => 100000,
    ], [
        ['sku' => 'SKU-CART-8', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9],
    ]);
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());
    $row->update(['quantity' => 5]);

    $cart = Livewire::actingAs($customer)->test(CartManager::class)
        ->assertSee('Cart');

    $row->variant()->first()->update(['stock' => 4]);

    // Re-mount so the row reads the new stock.
    Livewire::actingAs($customer)->test(CartManager::class)
        ->assertSee('only 4 left');

    expect($row->refresh()->quantity)->toBe(5);
});

test('a sold-out SKU refuses edits and shows the remove hint (FR-29)', function () {
    $product = productWithVariants([
        'base_price' => 100000,
    ], [
        ['sku' => 'SKU-CART-9', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());

    $product->variants->first()->update(['stock' => 0]);

    Livewire::actingAs($customer)->test(CartManager::class)
        ->call('setQty', $row->id, 3)
        ->assertSee('out of stock');

    expect($row->refresh()->quantity)->toBe(1);
});

test('removeItem clears the row right away (FR-28, FR-31)', function () {
    $product = productWithVariants();
    $customer = customer();

    $row = CartItem::mergeAdd($customer->id, $product->variants->first());

    Livewire::actingAs($customer)->test(CartManager::class)
        ->call('removeItem', $row->id)
        ->assertSee('Item removed from the cart.');

    expect(CartItem::query()->count())->toBe(0);
});

test('an empty cart shows the catalog guidance (FR-32 groundwork)', function () {
    Livewire::actingAs(customer())->test(CartManager::class)
        ->assertSee('Your cart is empty')
        ->assertSee(route('home'));
});

test('cart rows persist across sessions (FR-31)', function () {
    $product = productWithVariants();
    $customer = customer();

    Livewire::actingAs($customer)->test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart');

    $cart = Livewire::actingAs($customer)->test(CartManager::class);

    expect(CartItem::query()->count())->toBe(1)
        ->and($cart->html())->toContain($product->name);
});

test('a visitor pressing add-to-cart returns to the product area after signing in (FR-25)', function () {
    $product = productWithVariants();

    Livewire::test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart')
        ->assertRedirect(route('login'));

    $intended = session('url.intended');

    expect($intended)->not->toBeNull();

    $user = User::factory()->customer()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect($intended);
});
