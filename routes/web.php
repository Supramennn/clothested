<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Admin\AdminAccounts;
use App\Livewire\Admin\AdminChatPanel;
use App\Livewire\Admin\AdminOrderManager;
use App\Livewire\Admin\Catalog\Categories;
use App\Livewire\Admin\Catalog\Products\Form as AdminProductForm;
use App\Livewire\Admin\Catalog\Products\Index as AdminProducts;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CartManager;
use App\Livewire\CatalogPage;
use App\Livewire\CheckoutForm;
use App\Livewire\CustomerChat;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrdersShow;
use App\Livewire\ProductDetail;
use App\Livewire\Profile;
use App\Livewire\Wishlist;
use Illuminate\Support\Facades\Route;

Route::livewire('/', CatalogPage::class)->name('home');
Route::livewire('/products/{slug}', ProductDetail::class)->name('products.show');
Route::livewire('/category/{slug}', CatalogPage::class)->name('categories.show');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/register', Register::class)->name('register');
    Route::livewire('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::livewire('/profile', Profile::class)->name('profile');
    Route::livewire('/cart', CartManager::class)->name('cart');
    Route::livewire('/wishlist', Wishlist::class)->name('wishlist');
    Route::livewire('/chat', CustomerChat::class)->name('chat');
    Route::livewire('/checkout', CheckoutForm::class)->name('checkout');
    Route::livewire('/orders', OrdersIndex::class)->name('orders.index');
    Route::livewire('/orders/{orderCode}', OrdersShow::class)->name('orders.show');

    Route::post('/logout', LogoutController::class)->name('logout');
});

// Admin area; role middleware rejects every other account (FR-07).
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,super_admin'])->group(function (): void {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/categories', Categories::class)->name('categories');
    Route::livewire('/products', AdminProducts::class)->name('products.index');
    Route::livewire('/products/create', AdminProductForm::class)->name('products.create');
    Route::livewire('/products/{product}/edit', AdminProductForm::class)->name('products.edit');
    Route::livewire('/orders', AdminOrderManager::class)->name('orders.index');
    Route::livewire('/orders/{orderCode}', AdminOrderManager::class)->name('orders.show');
    Route::livewire('/chat', AdminChatPanel::class)->name('chat');
    Route::livewire('/reports/sales', SalesReport::class)->name('reports.sales');

    Route::livewire('/accounts', AdminAccounts::class)
        ->middleware('role:super_admin')
        ->name('accounts');
});
