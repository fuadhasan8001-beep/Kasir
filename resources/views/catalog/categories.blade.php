@extends('layouts.app')
@section('page-title', 'Kategori')
@section('content')
    <section class="category-hero page-intro" aria-labelledby="category-hero-title">
        <span class="category-hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        </span>
        <div>
            <h2 id="category-hero-title">Kategori &amp; Spesifikasi</h2>
            <p>Kelompokkan material dan atur atribut yang digunakan pada katalog produk.</p>
        </div>
    </section>
    <div class="category-page-content">
        <livewire:categories.manage-categories />
    </div>
@endsection
