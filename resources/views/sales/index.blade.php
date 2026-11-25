@extends('layouts.app')
@section('page-title', 'Transaksi')
@section('content')
    <header class="operations-hero page-intro"><div class="operations-hero-copy"><span class="operations-eyebrow">Riwayat penjualan</span><h2>Transaksi</h2><p>Pantau pembayaran, pelanggan, dan barang yang terjual.</p></div><a class="button-primary operations-hero-button" href="{{ route('kasir') }}"><span aria-hidden="true">＋</span> Transaksi baru</a></header>
    <livewire:sales.transaction-history />
@endsection
