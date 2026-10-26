<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingAddress;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

test('every ERD table 2.1-2.5 exists', function () {
    $tables = [
        'users', 'orders', 'order_items', 'payments', 'shipping_addresses',
        'categories', 'products', 'product_variants', 'product_images',
        'cart_items', 'wishlists', 'chat_sessions', 'chat_messages',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("table [{$table}] should exist");
    }
});

test('users carry the role column of ERD 2.1', function () {
    expect(Schema::hasColumn('users', 'role'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'phone'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'email_verified_at'))->toBeFalse();
});

test('order_items keep the snapshot columns of ERD 2.1', function () {
    foreach (['product_name', 'variant_label', 'unit_price', 'quantity', 'line_total'] as $column) {
        expect(Schema::hasColumn('order_items', $column))->toBeTrue();
    }
});

test('payments and shipping addresses are one-per-order per ERD 2.2', function () {
    $order = Order::factory()->create();

    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'midtrans_order_id' => 'TRX-20261026-TEST',
        'gross_amount' => 100000,
    ]);

    expect($payment->order_id)->toBe($order->id);

    ShippingAddress::query()->create([
        'order_id' => $order->id,
        'recipient_name' => 'Budi',
        'phone' => '081234567890',
        'address_line' => 'Jl. Melati No. 1',
        'city' => 'Bandung',
        'postal_code' => '40123',
    ]);

    expect(fn () => ShippingAddress::query()->create([
        'order_id' => $order->id,
        'recipient_name' => 'Sari',
        'phone' => '081234567891',
        'address_line' => 'Jl. Mawar No. 2',
        'city' => 'Jakarta',
        'postal_code' => '10123',
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('midtrans order id is unique across payments', function () {
    $first = Order::factory()->create();
    $second = Order::factory()->create();

    Payment::query()->create([
        'order_id' => $first->id,
        'midtrans_order_id' => 'TRX-SHARED-ID',
        'gross_amount' => 50000,
    ]);

    expect(fn () => Payment::query()->create([
        'order_id' => $second->id,
        'midtrans_order_id' => 'TRX-SHARED-ID',
        'gross_amount' => 50000,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
