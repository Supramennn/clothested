<?php

use App\Livewire\ProductDetail;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\Price;
use Livewire\Livewire;

test('product page shows breadcrumb, gallery, description, and category (FR-17)', function () {
    $product = productWithVariants(['description' => 'Woven from soft cotton.']);
    $product->images()->create(['image_path' => 'products/test-front.svg', 'sort_order' => 0]);

    $this->get("/products/{$product->slug}")
        ->assertOk()
        ->assertSee('Home')
        ->assertSee($product->category->name)
        ->assertSee($product->name)
        ->assertSee('Woven from soft cotton.')
        ->assertSee('storage/products/test-front.svg');

    expect($product->images->count())->toBe(1);
});

test('picking a variant updates the final price and the stock shown (FR-18, FR-19)', function () {
    $product = productWithVariants([
        'name' => 'Combo Tee',
        'base_price' => 100000,
    ], [
        ['sku' => 'SKU-DET-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
        ['sku' => 'SKU-DET-2', 'size' => 'M', 'color' => 'Krem', 'additional_price' => 20000, 'stock' => 3],
        ['sku' => 'SKU-DET-3', 'size' => 'L', 'color' => 'Krem', 'additional_price' => 50000, 'stock' => 0],
    ]);

    $this->get("/products/{$product->slug}")
        ->assertOk()
        ->assertSee(Price::format(100000));

    Livewire::test(ProductDetail::class, ['slug' => $product->slug])
        ->call('selectColor', 'Krem')
        ->assertSee(Price::format(120000));

    Livewire::test(ProductDetail::class, ['slug' => $product->slug])
        ->call('selectColor', 'Krem')
        ->call('selectSize', 'L')
        ->assertSee('Out of stock');
});

test('a SKU with stock 0 cannot be added to the cart from the product page (FR-20)', function () {
    $product = productWithVariants(['name' => 'Sold Out Tee'], [
        ['sku' => 'SKU-DET-0', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 0],
    ]);

    $this->get("/products/{$product->slug}")
        ->assertOk()
        ->assertSee('Out of stock');

    $customer = User::factory()->customer()->create();

    Livewire::actingAs($customer)->test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart');

    expect(CartItem::query()->count())->toBe(0);
});

test('a signed-in customer with an in-stock SKU gains a cart line when adding (FR-20)', function () {
    $product = productWithVariants(['name' => 'Stocked Tee'], [
        ['sku' => 'SKU-DET-4', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 5000, 'stock' => 8],
    ]);

    $customer = User::factory()->customer()->create();
    $variant = $product->variants->first();

    Livewire::actingAs($customer)->test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart')
        ->call('addToCart');

    $line = CartItem::query()->first();

    expect($line)->not->toBeNull()
        ->and($line->product_variant_id)->toBe($variant->id)
        ->and((int) $line->quantity)->toBe(2);
});

test('guests clicking add to cart or wishlist are sent to login first (FR-25 groundwork)', function () {
    $product = productWithVariants();

    Livewire::test(ProductDetail::class, ['slug' => $product->slug])
        ->call('addToCart')
        ->assertRedirect(route('login'));
});

test('wishlist toggles at product level for signed-in customers (FR-21)', function () {
    $product = productWithVariants();
    $customer = User::factory()->customer()->create();

    $testable = Livewire::actingAs($customer)->test(ProductDetail::class, ['slug' => $product->slug]);

    expect($testable->instance()->inWishlist())->toBeFalse();

    $testable->call('toggleWishlist');
    expect(Wishlist::query()->where('user_id', $customer->id)->where('product_id', $product->id)->exists())->toBeTrue();

    $testable->call('toggleWishlist');
    expect(Wishlist::query()->where('user_id', $customer->id)->count())->toBe(0);
});

test('deactivated products no longer open from their links (FR-22)', function () {
    $product = productWithVariants(['name' => 'Soon Gone']);

    $this->get("/products/{$product->slug}")->assertOk();

    $product->update(['is_active' => false]);

    $this->get("/products/{$product->slug}")->assertNotFound();
    $this->get('/products/no-such-slug')->assertNotFound();
});
