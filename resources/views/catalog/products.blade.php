@extends('layouts.app')
@section('page-title', 'Barang')
@section('content')
    <section class="category-hero products-hero page-intro" aria-labelledby="products-hero-title">
        <span class="category-hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="m3 12 9 4 9-4M3 17l9 4 9-4M12 11v10"/></svg>
        </span>
        <div>
            <h2 id="products-hero-title">Data Barang</h2>
            <p>Kelola katalog, harga, satuan, dan stok barang toko.</p>
        </div>
    </section>
    <livewire:products.manage-products />
@endsection
