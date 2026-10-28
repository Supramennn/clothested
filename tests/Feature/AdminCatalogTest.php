<?php

use App\Livewire\Admin\Catalog\Categories;
use App\Livewire\Admin\Catalog\Products\Form as ProductForm;
use App\Livewire\Admin\Catalog\Products\Index as ProductIndex;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('admins create categories and cannot reuse a category slug', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(Categories::class)
        ->set('name', 'Outerwear')
        ->set('slug', 'outerwear')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->where('slug', 'outerwear')->firstOrFail();

    Livewire::actingAs($admin)->test(Categories::class)
        ->set('name', 'Coats')
        ->set('slug', 'outerwear')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);

    expect(Category::query()->where('slug', 'outerwear')->count())->toBe(1)
        ->and($category->refresh()->name)->toBe('Outerwear');
});

test('category removal is refused while products still use it', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($admin)->test(Categories::class)
        ->call('deleteCategory', $product->category_id)
        ->assertSee('Move or remove the category products before deleting it.');

    expect(Category::query()->whereKey($product->category_id)->exists())->toBeTrue();
});

test('admins create products with unique SKUs and publish only products with variants', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    Livewire::actingAs($admin)->test(ProductForm::class)
        ->set('categoryId', $category->id)
        ->set('name', 'Harbor Jacket')
        ->set('slug', 'harbor-jacket')
        ->set('basePrice', '180000')
        ->set('isActive', true)
        ->call('addVariant')
        ->set('variants.0.sku', 'SKU-HARBOR-M')
        ->set('variants.0.color', 'Navy')
        ->set('variants.0.stock', 6)
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('slug', 'harbor-jacket')->firstOrFail();

    expect($product->is_active)->toBeTrue()
        ->and($product->variants()->where('sku', 'SKU-HARBOR-M')->value('stock'))->toBe(6);

    Livewire::actingAs($admin)->test(ProductForm::class)
        ->set('categoryId', $category->id)
        ->set('name', 'Draft Jacket')
        ->set('slug', 'draft-jacket')
        ->set('basePrice', '120000')
        ->set('isActive', true)
        ->call('save')
        ->assertHasErrors('variants');

    expect(Product::query()->where('slug', 'draft-jacket')->exists())->toBeFalse();
});

test('existing product SKUs reject duplicates and stock adjustments reject negative values', function () {
    $admin = User::factory()->admin()->create();
    $product = productWithVariants(['name' => 'Field Shirt'], [
        ['sku' => 'SKU-FIELD-M', 'size' => 'M', 'color' => 'Olive', 'additional_price' => 0, 'stock' => 4],
    ]);
    $variant = $product->variants->firstOrFail();

    Livewire::actingAs($admin)->test(ProductForm::class, ['product' => $product])
        ->set('variants.0.sku', 'SKU-FIELD-M')
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    Livewire::actingAs($admin)->test(ProductForm::class)
        ->set('categoryId', $product->category_id)
        ->set('name', 'Second Field Shirt')
        ->set('slug', 'second-field-shirt')
        ->set('basePrice', '120000')
        ->call('addVariant')
        ->set('variants.0.sku', 'SKU-FIELD-M')
        ->set('variants.0.color', 'Black')
        ->call('save')
        ->assertHasErrors('variants.0.sku');

    Livewire::actingAs($admin)->test(ProductIndex::class)
        ->set('stockValues.'.$variant->id, -1)
        ->call('adjustStock', $variant->id)
        ->assertHasErrors('stockValues.'.$variant->id);

    expect($variant->refresh()->stock)->toBe(4);
});

test('products referenced by a customer cart cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $product = productWithVariants();
    $variant = $product->variants->firstOrFail();
    CartItem::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    Livewire::actingAs($admin)->test(ProductIndex::class)
        ->call('deleteProduct', $product->id)
        ->assertHasErrors('product');

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});
