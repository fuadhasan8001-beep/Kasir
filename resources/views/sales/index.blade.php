@extends('layouts.app')
@section('page-title', 'Transaksi')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Riwayat penjualan</h2><p>Rekap catatan transaksi dan nilai barang yang tersimpan.</p></div><a class="button-primary" href="{{ route('kasir') }}">Catat transaksi</a></div>
    <livewire:sales.transaction-history />
@endsection
