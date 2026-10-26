<?php

use App\Livewire\CatalogPage;
use App\Models\Category;
use App\Models\Product;
use App\Support\Price;
use Livewire\Livewire;

test('catalog lists active products only with images, names, prices, and badges (FR-09, FR-10)', function () {
    $category = Category::factory()->create();

    $active = Product::factory()->for($category)->create(['name' => 'Rise Tee Navy']);
    $active->variants()->create(['sku' => 'SKU-CAT-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 10]);

    $featured = Product::factory()->for($category)->featured()->create(['name' => 'Flow Dress Rose']);
    $featured->variants()->create(['sku' => 'SKU-CAT-2', 'size' => 'S', 'color' => 'Rose', 'additional_price' => 0, 'stock' => 4]);

    $inactive = Product::factory()->for($category)->inactive()->create(['name' => 'Retired Shirt']);
    $inactive->variants()->create(['sku' => 'SKU-CAT-3', 'size' => 'L', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 7]);

    Livewire::test(CatalogPage::class)
        ->assertSee('Rise Tee Navy')
        ->assertSee('Flow Dress Rose')
        ->assertSee(Price::format((float) $active->base_price))
        ->assertSee('Featured')
        ->assertDontSee('Retired Shirt');
});

test('text search finds names and descriptions (FR-11)', function () {
    $category = Category::factory()->create();

    $tee = Product::factory()->for($category)->create(['name' => 'Graphic Rise Tee']);
    $tee->variants()->create(['sku' => 'SKU-Q-1', 'size' => 'M', 'color' => 'Hitam', 'additional_price' => 0, 'stock' => 5]);

    $other = Product::factory()->for($category)->create(['name' => 'Plain Chino']);
    $other->variants()->create(['sku' => 'SKU-Q-2', 'size' => 'M', 'color' => 'Krem', 'additional_price' => 0, 'stock' => 5]);

    Livewire::test(CatalogPage::class)
        ->set('q', 'rise')
        ->assertSee('Graphic Rise Tee')
        ->assertDontSee('Plain Chino');
});

test('category, size, color, and price filters narrow results together (FR-12)', function () {
    $jackets = Category::factory()->create(['name' => 'Jacket', 'slug' => 'jacket']);
    $tees = Category::factory()->create(['name' => 'Tee', 'slug' => 'tee']);

    $cheapMatching = Product::factory()->for($jackets)->create(['name' => 'Budget Jacket', 'base_price' => 150000]);
    $cheapMatching->variants()->create(['sku' => 'SKU-F-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $pricierMatching = Product::factory()->for($jackets)->create(['name' => 'Premium Jacket', 'base_price' => 480000]);
    $pricierMatching->variants()->create(['sku' => 'SKU-F-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $wrongSize = Product::factory()->for($jackets)->create(['name' => 'XL Jacket', 'base_price' => 120000]);
    $wrongSize->variants()->create(['sku' => 'SKU-F-3', 'size' => 'XL', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $wrongCategory = Product::factory()->for($tees)->create(['name' => 'Combo Tee', 'base_price' => 100000]);
    $wrongCategory->variants()->create(['sku' => 'SKU-F-4', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    Livewire::test(CatalogPage::class)
        ->set('category', 'jacket')
        ->set('maxPrice', 300000)
        ->assertSee('Budget Jacket')
        ->assertDontSee('Premium Jacket')
        ->assertDontSee('Combo Tee');

    Livewire::test(CatalogPage::class)
        ->set('category', 'jacket')
        ->set('sizes', ['M'])
        ->set('colors', ['navy'])
        ->set('maxPrice', 500000)
        ->assertSee('Budget Jacket')
        ->assertSee('Premium Jacket')
        ->assertDontSee('XL Jacket')
        ->assertDontSee('Combo Tee');
});

test('a URL carrying filter state reproduces the same list when reopened (FR-14)', function () {
    $tees = Category::factory()->create(['name' => 'Tee', 'slug' => 'tee']);

    $cheap = Product::factory()->for($tees)->create(['name' => 'Pocket Tee', 'base_price' => 99000]);
    $cheap->variants()->create(['sku' => 'SKU-U-1', 'size' => 'S', 'color' => 'Krem', 'additional_price' => 0, 'stock' => 9]);

    $costly = Product::factory()->for($tees)->create(['name' => 'Coated Tee', 'base_price' => 390000]);
    $costly->variants()->create(['sku' => 'SKU-U-2', 'size' => 'S', 'color' => 'Krem', 'additional_price' => 0, 'stock' => 9]);

    $this->get('/?category=tee&maxPrice=100000')
        ->assertOk()
        ->assertSee('Pocket Tee')
        ->assertDontSee('Coated Tee');
});

test('sorting by price orders the list correctly (FR-13)', function () {
    $category = Category::factory()->create();

    $mid = Product::factory()->for($category)->create(['name' => 'Mid Tee', 'base_price' => 200000]);
    $mid->variants()->create(['sku' => 'SKU-S-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $cheapest = Product::factory()->for($category)->create(['name' => 'Cheap Tee', 'base_price' => 70000]);
    $cheapest->variants()->create(['sku' => 'SKU-S-2', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $costliest = Product::factory()->for($category)->create(['name' => 'Costly Tee', 'base_price' => 500000]);
    $costliest->variants()->create(['sku' => 'SKU-S-3', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $html = Livewire::test(CatalogPage::class)
        ->set('sort', 'price_low')
        ->html();

    expect(mb_strpos($html, 'Cheap Tee'))->toBeLessThan(mb_strpos($html, 'Mid Tee'))
        ->and(mb_strpos($html, 'Mid Tee'))->toBeLessThan(mb_strpos($html, 'Costly Tee'));
});

test('pagination shows 24 products per page (FR-15)', function () {
    $category = Category::factory()->create();

    foreach (range(1, 26) as $count) {
        $product = Product::factory()->for($category)->create(['name' => "Batch Tee {$count}"]);
        $product->variants()->create(['sku' => "SKU-P-{$count}", 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);
    }

    $testable = Livewire::test(CatalogPage::class);

    expect($testable->instance()->products()->perPage())->toBe(24)
        ->and($testable->instance()->products()->lastPage())->toBeGreaterThan(1);
});

test('category pages use the slug URL and reject unknown slugs (FR-16)', function () {
    $tees = Category::factory()->create(['name' => 'Tee', 'slug' => 'tee']);

    $product = Product::factory()->for($tees)->create(['name' => 'Slug Tee']);
    $product->variants()->create(['sku' => 'SKU-SLG-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 9]);

    $this->get('/category/tee')->assertOk()->assertSee('Slug Tee')->assertSee('Tee');
    $this->get('/category/no-such-slug')->assertNotFound();
});
