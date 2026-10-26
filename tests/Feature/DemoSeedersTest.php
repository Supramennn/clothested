<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

test('seeding creates one demo account for every role', function () {
    $this->seed();

    foreach (['super_admin' => 'superadmin@clothested.test', 'admin' => 'admin@clothested.test', 'customer' => 'customer@clothested.test'] as $role => $email) {
        $user = User::query()->where('email', $email)->first();

        expect($user)->not->toBeNull()
            ->and($user->role)->toBe($role);
    }
});

test('seeded catalog has categories, published products with variants, and one hidden product', function () {
    $this->seed();

    expect(Category::count())->toBe(5)
        ->and(Product::query()->where('is_active', true)->count())->toBe(7)
        ->and(Product::query()->where('is_active', false)->count())->toBe(1);

    $published = Product::query()->where('is_active', true)->get();

    foreach ($published as $product) {
        expect($product->variants()->count())->toBeGreaterThan(0)
            ->and($product->images()->count())->toBeGreaterThan(0);
    }
});

test('seeded variants stay within the valid size set and brick SKU is unique', function () {
    $this->seed();

    $sizes = ['S', 'M', 'L', 'XL'];

    expect(ProductVariant::query()->pluck('size')->unique()->every(fn ($size) => in_array($size, $sizes)))->toBeTrue()
        ->and(ProductVariant::query()->pluck('sku')->unique()->count())
        ->toBe(ProductVariant::count());
});
