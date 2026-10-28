<?php

use App\Models\User;

test('guests hitting the admin area are routed to login (FR-07)', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('customers opening admin links go home with an access denied message (FR-07)', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get('/admin')
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Access denied: this area is limited to store staff.');

    $this->followingRedirects()->get('/admin')->assertSee('Access denied');
});

test('admins and super admins pass the role middleware (FR-07)', function () {
    $admin = User::factory()->admin()->create();

    foreach ([
        'admin.dashboard',
        'admin.categories',
        'admin.products.index',
        'admin.products.create',
        'admin.orders.index',
        'admin.reports.sales',
    ] as $routeName) {
        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/admin')
        ->assertOk();
});

test('customers cannot access admin operations pages', function () {
    $customer = User::factory()->customer()->create();

    foreach ([
        'admin.categories',
        'admin.products.index',
        'admin.products.create',
        'admin.orders.index',
        'admin.reports.sales',
        'admin.accounts',
    ] as $routeName) {
        $this->actingAs($customer)
            ->get(route($routeName))
            ->assertRedirect(route('home'));
    }
});
