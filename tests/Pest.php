<?php

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
|
| Shared helpers used across the ClothesTed test suites.
|
*/

/**
 * Register and sign in as a customer, returning the user.
 */
function customer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

/**
 * Creates a product with one or more SKUs and returns the product.
 *
 * @param  array<string, mixed>|null  $variants
 */
function productWithVariants(array $productAttributes = [], ?array $variants = null): Product
{
    $product = Product::factory()->create($productAttributes);
    $variants ??= [
        ['sku' => 'SKU-DET-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10],
    ];

    foreach ($variants as $variant) {
        $product->variants()->create($variant);
    }

    return $product->refresh();
}
