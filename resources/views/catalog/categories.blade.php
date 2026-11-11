@extends('layouts.app')
@section('page-title', 'Kategori')
@section('content')
    <div class="welcome-row page-intro">
        <div>
            <h2>Kategori & spesifikasi</h2>
            <p>Kelompokkan material dan atur atribut yang digunakan pada katalog produk.</p>
        </div>
    </div>
    <livewire:categories.manage-categories />
@endsection
