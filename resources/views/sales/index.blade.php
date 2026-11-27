@extends('layouts.app')
@section('page-title', 'Transaksi')
@section('content')
    <header class="operations-hero page-intro"><div class="operations-hero-copy"><span class="operations-eyebrow">Riwayat penjualan</span><h2>Transaksi</h2><p>Lihat dan cari transaksi yang sudah berhasil dibayar.</p></div></header>
    <livewire:sales.transaction-history />
@endsection
