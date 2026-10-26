<?php

use App\Livewire\Profile;
use App\Models\User;
use Livewire\Livewire;

test('guests are sent to login when visiting the profile page (FR-06)', function () {
    $this->get('/profile')->assertRedirect(route('login'));
});

test('a customer updates name and phone while the email stays locked (FR-06)', function () {
    $user = User::factory()->customer()->create([
        'email' => 'sinta@example.com',
        'name' => 'Sinta',
        'phone' => '081234567890',
    ]);

    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', 'Sinta Dewi')
        ->set('phone', '089999999999')
        ->call('save')
        ->assertNoRedirect();

    $user->refresh();

    expect($user->name)->toBe('Sinta Dewi')
        ->and($user->phone)->toBe('089999999999')
        ->and($user->email)->toBe('sinta@example.com');
});

test('profile saving needs a name and accepts an optional phone (FR-06)', function () {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', '')
        ->set('phone', '081200000009')
        ->call('save')
        ->assertHasErrors(['name']);

    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', 'Sinta Dewi')
        ->set('phone', '')
        ->call('save')
        ->assertHasNoErrors();
});

test('the profile form rejects phones beyond the 20 character column (FR-06)', function () {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', 'Sinta Dewi')
        ->set('phone', '0812345678901234567890')
        ->call('save')
        ->assertHasErrors(['phone']);
});
