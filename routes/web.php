<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'))->name('home');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/kasir', 'sales.create')->name('kasir');
    Route::view('/kasir/keranjang', 'sales.cart')->name('kasir.cart');
    Route::view('/laporan', 'reports.index')->name('reports.index');
    Route::redirect('/reports', '/laporan');
    Route::view('/catatan', 'inventory.index')->name('catatan');
    Route::view('/riwayat', 'sales.index')->name('riwayat');
    Route::view('/sales/create', 'sales.create')->name('sales.create');
    Route::view('/transactions', 'sales.index')->name('sales.index');
    Route::view('/inventory', 'inventory.index')->name('inventory.index');
    Route::view('/products', 'catalog.products')->name('products.index');
    Route::view('/categories', 'catalog.categories')->name('categories.index');
    Route::redirect('/expenses', '/laporan#pengeluaran')->name('expenses.index');
    Route::view('/pesanan', 'sales.create')->name('orders.custom');
    Route::redirect('/orders', '/pesanan');
    Route::redirect('/orders/custom', '/pesanan');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
