<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('a customer logs in and lands on the store home (FR-03, FR-04)', function () {
    $user = User::factory()->customer()->create(['email' => 'sinta@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'sinta@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirectToRoute('home');

    expect(Auth::user()?->is($user))->toBeTrue();
});

test('an admin and a super admin land on the dashboard after login (FR-04)', function () {
    User::factory()->admin()->create(['email' => 'staff@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'staff@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirectToRoute('admin.dashboard');

    User::factory()->superAdmin()->create(['email' => 'chief@example.com']);

    Auth::logout();

    Livewire::test(Login::class)
        ->set('email', 'chief@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirectToRoute('admin.dashboard');
});

test('a page requested from a protected area is restored after login (FR-04)', function () {
    $user = User::factory()->customer()->create();

    $this->get('/profile')->assertRedirect(route('login'));

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/profile');
});

test('the same generic failure appears for an unknown email and a wrong password (FR-03)', function () {
    $known = User::factory()->create();

    $unknownEmailFailure = Livewire::test(Login::class)
        ->set('email', 'nobody@example.com')
        ->set('password', 'whatever123')
        ->call('login');

    $wrongPasswordFailure = Livewire::test(Login::class)
        ->set('email', $known->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $message = 'These credentials do not match our records.';

    expect($unknownEmailFailure->instance()->authError)->toBe($message)
        ->and($wrongPasswordFailure->instance()->authError)->toBe($message)
        ->and(Auth::check())->toBeFalse();
});

test('repeated failures lock the login briefly even with correct credentials (FR-03)', function () {
    User::factory()->create(['email' => 'sinta@example.com']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', 'sinta@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    $throttleKey = Str::lower('sinta@example.com').'|'.request()->ip();

    expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeTrue();

    Livewire::test(Login::class)
        ->set('email', 'sinta@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertNoRedirect();

    expect(Auth::check())->toBeFalse();
});

test('the session identifier is regenerated at login (FR-05)', function () {
    $user = User::factory()->create(['email' => 'sinta@example.com']);

    $this->get('/login');

    $before = session()->getId();

    Livewire::test(Login::class)
        ->set('email', 'sinta@example.com')
        ->set('password', 'password')
        ->call('login');

    expect(session()->getId())->not->toBe($before)
        ->and(Auth::user()?->is($user))->toBeTrue();
});

test('logout invalidates the session and clears the account (FR-05)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $before = session()->getId();

    $response = $this->post('/logout');

    $response->assertRedirectToRoute('home');
    $this->assertGuest();
    expect(session()->getId())->not->toBe($before);
});
