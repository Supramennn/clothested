<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('registration creates a customer account and signs the user in immediately (FR-01, FR-02)', function () {
    Livewire::test(Register::class)
        ->set('name', 'Sinta Dewi')
        ->set('email', 'sinta@example.com')
        ->set('phone', '081234567890')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('home'));

    $user = User::query()->where('email', 'sinta@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Sinta Dewi')
        ->and($user->phone)->toBe('081234567890')
        ->and($user->role)->toBe('customer')
        ->and(Auth::user()?->is($user))->toBeTrue();
});

test('registration rejects blanks, bad emails, and short passwords inline (FR-01)', function () {
    Livewire::test(Register::class)
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('password', 'short')
        ->call('register')
        ->assertNoRedirect()
        ->assertHasErrors(['name', 'email', 'password']);

    $this->assertGuest();
});

test('registering a duplicate email fails plainly while the form keeps its values (FR-01)', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'Sinta Dewi')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertNoRedirect()
        ->assertHasErrors(['email'])
        ->assertSnapshotSet('email', 'taken@example.com')
        ->assertSee('The email has already been taken.');

    $this->assertGuest();
    expect(User::query()->where('name', 'Sinta Dewi')->count())->toBe(0);
});

test('register confirmation mismatch keeps the account from being created (FR-01)', function () {
    Livewire::test(Register::class)
        ->set('name', 'Sinta Dewi')
        ->set('email', 'sinta@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different123')
        ->call('register')
        ->assertNoRedirect()
        ->assertHasErrors(['password']);

    expect(User::query()->where('email', 'sinta@example.com')->exists())->toBeFalse();
});
