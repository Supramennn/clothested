<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

test('a product is only active when it is flagged active and has variants (FR-09, FR-24)', function () {
    $product = Product::factory()->create(['is_active' => false]);

    expect(Product::active()->where('id', $product->id)->exists())->toBeFalse();

    $product->update(['is_active' => true]);

    expect(Product::active()->where('id', $product->id)->exists())->toBeFalse();

    $product->variants()->create([
        'sku' => 'SKU-ACTIVE-1',
        'size' => 'M',
        'color' => 'Navy',
        'additional_price' => 0,
        'stock' => 10,
    ]);

    expect(Product::active()->where('id', $product->id)->exists())->toBeTrue();
});

test('variant stock availability never allows overselling (FR-27)', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create(['stock' => 5]);

    expect($variant->isStockAvailable(1))->toBeTrue()
        ->and($variant->isStockAvailable(5))->toBeTrue()
        ->and($variant->isStockAvailable(6))->toBeFalse()
        ->and($variant->isStockAvailable(0))->toBeFalse();
});

test('variant label follows the color and size snapshot of ERD 2.1', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create([
        'color' => 'Navy',
        'size' => 'L',
    ]);

    expect($variant->label())->toBe('Navy / L');
});

test('orders are cancellable by the customer only while pending (FR-40)', function () {
    $pending = Order::factory()->create(['status' => 'pending']);
    $processing = Order::factory()->create(['status' => 'processing']);
    $shipped = Order::factory()->create(['status' => 'shipped']);

    expect($pending->isCancellable())->toBeTrue()
        ->and($processing->isCancellable())->toBeFalse()
        ->and($shipped->isCancellable())->toBeFalse();
});
