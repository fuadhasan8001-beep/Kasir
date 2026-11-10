<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::view('/products', 'catalog.products')->name('products.index');
    Route::view('/sales/create', 'sales.create')->name('sales.create');
    Route::view('/orders/custom', 'orders.custom')->name('orders.custom');
    Route::view('/inventory', 'inventory.index')->name('inventory.index');
    Route::view('/expenses', 'finance.expenses')->name('expenses.index');
    Route::view('/transactions', 'sales.index')->name('sales.index');
    Route::view('/categories', 'catalog.categories')->name('categories.index');
    Route::view('/units', 'catalog.units')->name('units.index');
});
