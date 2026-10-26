<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Profile;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/register', Register::class)->name('register');
    Route::livewire('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::livewire('/profile', Profile::class)->name('profile');

    Route::post('/logout', LogoutController::class)->name('logout');
});

// Admin area; role middleware rejects every other account (FR-07).
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,super_admin'])->group(function (): void {
    Route::livewire('/', Dashboard::class)->name('dashboard');
});
