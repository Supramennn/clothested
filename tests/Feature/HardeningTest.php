<?php

use App\Livewire\Auth\Register;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

/**
 * Phase 9: the nonfunctional requirements of PRD section 6 that the
 * application itself can prove — rate limits, SEO, and structured logs.
 */
test('sign-up attempts from one address are capped (security)', function () {
    for ($i = 1; $i <= 5; $i++) {
        Livewire::test(Register::class)
            ->set('name', "Customer {$i}")
            ->set('email', "customer{$i}@clothested.test")
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');
    }

    expect(User::query()->count())->toBe(5);

    $sixth = Livewire::test(Register::class)
        ->set('name', 'Customer 6')
        ->set('email', 'customer6@clothested.test')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register');

    expect(implode(' ', $sixth->errors()->get('email')))->toContain('Too many registration attempts.')
        ->and($sixth->errors()->get('email')[0])->toContain('seconds.')
        ->and(User::query()->count())->toBe(5);
});

test('the product page carries its title, description, and structured data (SEO)', function () {
    productWithVariants([
        'name' => 'Structured Dress',
        'slug' => 'structured-dress',
        'description' => '<p>A structured dress story with honest labels.</p>',
        'base_price' => 129000,
    ], [
        ['sku' => 'SKU-SEO-A', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 4],
        ['sku' => 'SKU-SEO-B', 'size' => 'L', 'color' => 'Navy', 'additional_price' => 10000, 'stock' => 0],
    ]);

    $response = $this->get('/products/structured-dress');

    $response->assertOk()
        ->assertSee('<title>Structured Dress — ClothesTed</title>', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('A structured dress story with honest labels.', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Product"', false);

    // The structured document prices every SKU with its availability.
    expect($response->getContent())
        ->toContain(json_encode('SKU-SEO-A', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT))
        ->toContain(json_encode('https://schema.org/OutOfStock', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
});

test('the catalog page keeps a clear title and description (SEO)', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>Catalog — ClothesTed</title>', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('New arrivals and wardrobe staples', false);
});

test('order status changes leave one structured log line (observability)', function () {
    $order = courierOrderForHardening();

    Log::spy();

    app(OrderService::class)->adminCancel($order, 'The customer asked through the chat.');

    Log::assertLogged('info', function (string $message, array $context): bool {
        return $message === 'An order status was updated.'
            && $context['action'] === 'cancelled'
            && $context['status'] === 'cancelled'
            && $context['order_code'] !== null;
    });

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->refund_note)->toBe('The customer asked through the chat.');
});

/**
 * A courier order through the real checkout path, ready for an admin cancel.
 */
function courierOrderForHardening(): Order
{
    $customer = customer();
    $product = productWithVariants(['name' => 'Hardening Dress', 'base_price' => 50000], [
        ['sku' => 'SKU-HARD-1', 'size' => 'M', 'color' => 'Navy', 'additional_price' => 0, 'stock' => 5],
    ]);
    $variant = $product->variants->firstOrFail();

    CartItem::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    return app(OrderService::class)->placeOrder($customer, [
        'shipping_method' => 'courier',
        'note' => null,
        'address' => [
            'recipient_name' => $customer->name,
            'phone' => '081234567890',
            'address_line' => 'Jl. Testing No. 9',
            'city' => 'Jakarta',
            'postal_code' => '10110',
        ],
    ]);
}
