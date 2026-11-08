<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CashierController;
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

    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('cashiers', CashierController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('permission:catalog.view')->group(function () {
        Route::view('/products', 'catalog.products')->name('products.index');
    });

    Route::middleware('permission:sales.create')->group(function () {
        Route::view('/sales/create', 'sales.create')->name('sales.create');
        Route::view('/orders/custom', 'orders.custom')->name('orders.custom');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::view('/inventory', 'inventory.index')->name('inventory.index');
    });

    Route::middleware('permission:finance.manage')->group(function () {
        Route::view('/expenses', 'finance.expenses')->name('expenses.index');
    });

    Route::view('/transactions', 'sales.index')->name('sales.index');

    Route::middleware('permission:catalog.manage')->group(function () {
        Route::view('/categories', 'catalog.categories')->name('categories.index');
        Route::view('/units', 'catalog.units')->name('units.index');
    });
});
