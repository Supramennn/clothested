<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Demo accounts for every role (FR-02, FR-04, FR-07).
     * Passwords are local demo values; rotate before any real deployment.
     */
    public function run(): void
    {
        User::query()->updateOrInsert(
            ['email' => 'superadmin@clothested.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'phone' => '081200000001',
                'role' => 'super_admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        User::query()->updateOrInsert(
            ['email' => 'admin@clothested.test'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('admin123'),
                'phone' => '081200000002',
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        User::query()->updateOrInsert(
            ['email' => 'customer@clothested.test'],
            [
                'name' => 'Customer Demo',
                'password' => Hash::make('customer123'),
                'phone' => '081200000003',
                'role' => 'customer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
