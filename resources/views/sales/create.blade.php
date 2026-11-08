@extends('layouts.app')
@section('page-title', 'Kasir')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Transaksi penjualan</h2><p>Pilih barang, masukkan spesifikasi pesanan, lalu selesaikan pembayaran.</p></div></div>
    <livewire:sales.point-of-sale />
@endsection
