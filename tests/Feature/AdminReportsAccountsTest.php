<?php

use App\Livewire\Admin\AdminAccounts;
use App\Livewire\Admin\SalesReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function reportPaymentFor(Order $order, string $status): Payment
{
    return Payment::query()->create([
        'order_id' => $order->id,
        'midtrans_order_id' => $order->order_code,
        'gross_amount' => $order->total,
        'transaction_status' => $status,
        'paid_at' => $status === 'settlement' ? $order->placed_at : null,
    ]);
}

test('sales report includes settled non-cancelled orders in the selected date range', function () {
    $this->travelTo('2026-10-27 12:00:00');
    $admin = User::factory()->admin()->create();

    $settled = Order::factory()->for(customer())->processing()->create([
        'order_code' => 'TRX-REPORT-PAID-1',
        'total' => 75000,
        'placed_at' => '2026-10-25 10:00:00',
    ]);
    reportPaymentFor($settled, 'settlement');

    $placedEarlier = Order::factory()->for(customer())->processing()->create([
        'order_code' => 'TRX-REPORT-LATE-PAID-1',
        'total' => 50000,
        'placed_at' => '2026-10-23 10:00:00',
    ]);
    reportPaymentFor($placedEarlier, 'settlement')->update(['paid_at' => '2026-10-26 14:00:00']);

    $paidLater = Order::factory()->for(customer())->processing()->create([
        'order_code' => 'TRX-REPORT-PAID-LATER-1',
        'total' => 35000,
        'placed_at' => '2026-10-26 10:00:00',
    ]);
    reportPaymentFor($paidLater, 'settlement')->update(['paid_at' => '2026-10-28 10:00:00']);

    $pending = Order::factory()->for(customer())->create([
        'order_code' => 'TRX-REPORT-PENDING-1',
        'total' => 25000,
        'placed_at' => '2026-10-26 10:00:00',
    ]);
    reportPaymentFor($pending, 'pending');

    $cancelled = Order::factory()->for(customer())->cancelled()->create([
        'order_code' => 'TRX-REPORT-CANCELLED-1',
        'total' => 50000,
        'placed_at' => '2026-10-26 11:00:00',
    ]);
    reportPaymentFor($cancelled, 'settlement');

    Livewire::actingAs($admin)->test(SalesReport::class)
        ->set('fromDate', '2026-10-24')
        ->set('toDate', '2026-10-26')
        ->call('runReport')
        ->assertSee('Rp 125.000')
        ->assertSee('TRX-REPORT-PAID-1')
        ->assertSee('TRX-REPORT-LATE-PAID-1')
        ->assertDontSee('TRX-REPORT-PENDING-1')
        ->assertDontSee('TRX-REPORT-PAID-LATER-1')
        ->assertDontSee('TRX-REPORT-CANCELLED-1');
});

test('sales report rejects a range that ends before it starts', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test(SalesReport::class)
        ->set('fromDate', '2026-10-27')
        ->set('toDate', '2026-10-26')
        ->call('runReport')
        ->assertHasErrors(['toDate' => 'after_or_equal']);
});

test('only super admins can manage admin accounts', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.accounts'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->actingAs(customer())
        ->get(route('admin.accounts'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

test('super admins create admin accounts and leave blank edit passwords unchanged', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->get(route('admin.accounts'))->assertOk();

    Livewire::actingAs($superAdmin)->test(AdminAccounts::class)
        ->set('name', 'Store Operator')
        ->set('email', 'operator@clothested.test')
        ->set('phone', '081234567890')
        ->set('password', 'operator-pass-123')
        ->call('save')
        ->assertHasNoErrors();

    $admin = User::query()->where('email', 'operator@clothested.test')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and(Hash::check('operator-pass-123', $admin->password))->toBeTrue();

    Livewire::actingAs($superAdmin)->test(AdminAccounts::class)
        ->call('editAdmin', $admin->id)
        ->set('name', 'Store Operator Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->refresh()->name)->toBe('Store Operator Updated')
        ->and(Hash::check('operator-pass-123', $admin->password))->toBeTrue();
});

test('admin accounts with order history cannot be deleted', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();
    Order::factory()->for($admin)->create();

    Livewire::actingAs($superAdmin)->test(AdminAccounts::class)
        ->call('deleteAdmin', $admin->id)
        ->assertSee('This account has order history and cannot be deleted.');

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});
