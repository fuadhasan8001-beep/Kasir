<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    Route::middleware('permission:catalog.view')->group(function () {
        Route::view('/products', 'catalog.products')->name('products.index');
    });

    Route::middleware('permission:catalog.manage')->group(function () {
        Route::view('/categories', 'catalog.categories')->name('categories.index');
        Route::view('/units', 'catalog.units')->name('units.index');
    });
});
