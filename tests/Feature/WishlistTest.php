<?php

use App\Livewire\Wishlist;
use App\Models\CartItem;
use App\Models\Wishlist as WishlistModel;
use Livewire\Livewire;

test('guests are sent to login before seeing the wishlist (FR-25)', function () {
    $this->get('/wishlist')->assertRedirect(route('login'));
});

test('move-to-cart picks the first SKU of the product that still has stock (FR-30)', function () {
    $product = productWithVariants(['base_price' => 100000], [
        ['sku' => 'SKU-WSH-1', 'size' => 'S', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 0],
        ['sku' => 'SKU-WSH-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
        ['sku' => 'SKU-WSH-3', 'size' => 'L', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 7],
    ]);
    $customer = customer();

    $saved = WishlistModel::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    Livewire::actingAs($customer)->test(Wishlist::class)
        ->call('moveToCart', $saved->id)
        ->assertSee('moved into the cart');

    $stockedVariant = $product->variants->firstWhere('sku', 'SKU-WSH-2');
    $row = CartItem::query()->sole();

    expect($row->product_variant_id)->toBe($stockedVariant->id)
        ->and($row->quantity)->toBe(1)
        ->and(WishlistModel::query()->count())->toBe(0);
});

test('move-to-cart merges into the existing row for the same SKU (FR-26, FR-30)', function () {
    $product = productWithVariants(['base_price' => 100000], [
        ['sku' => 'SKU-WSH-4', 'size' => 'S', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 0],
        ['sku' => 'SKU-WSH-5', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 25],
    ]);
    $customer = customer();

    $stockedVariant = $product->variants->firstWhere('sku', 'SKU-WSH-5');

    $existingRow = CartItem::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $stockedVariant->id,
        'quantity' => 22,
    ]);

    $saved = WishlistModel::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    Livewire::actingAs($customer)->test(Wishlist::class)
        ->call('moveToCart', $saved->id)
        ->assertSee('moved into the cart');

    expect(CartItem::query()->count())->toBe(1)
        ->and($existingRow->refresh()->quantity)->toBe(23)
        ->and(WishlistModel::query()->count())->toBe(0);
});

test('a product with no stock anywhere stays saved with a notice (FR-30)', function () {
    $product = productWithVariants(['name' => 'Dry Season Dress'], [
        ['sku' => 'SKU-WSH-6', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 0],
    ]);
    $customer = customer();

    $saved = WishlistModel::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    Livewire::actingAs($customer)->test(Wishlist::class)
        ->call('moveToCart', $saved->id)
        ->assertSee('is in stock right now');

    expect(WishlistModel::query()->count())->toBe(1)
        ->and(WishlistModel::query()->first()->id)->toBe($saved->id)
        ->and(CartItem::query()->count())->toBe(0);
});

test('a product no longer on sale cannot move and stays saved (FR-30)', function () {
    $product = productWithVariants(['name' => 'Retired Blazer', 'is_active' => false]);
    $customer = customer();

    $saved = WishlistModel::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    Livewire::actingAs($customer)->test(Wishlist::class)
        ->call('moveToCart', $saved->id)
        ->assertSee('no longer on sale');

    expect(WishlistModel::query()->count())->toBe(1)
        ->and(CartItem::query()->count())->toBe(0);
});

test('remove deletes the saved product (FR-31)', function () {
    $product = productWithVariants();
    $customer = customer();

    $saved = WishlistModel::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    Livewire::actingAs($customer)->test(Wishlist::class)
        ->assertSee($product->name)
        ->call('remove', $saved->id)
        ->assertSee('Removed from the wishlist.');

    expect(WishlistModel::query()->count())->toBe(0);
});
