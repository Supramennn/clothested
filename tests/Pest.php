<?php

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
