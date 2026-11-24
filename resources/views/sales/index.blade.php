@extends('layouts.app')
@section('page-title', 'Transaksi')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Transaksi</h2><p>Riwayat pembayaran dan barang yang terjual.</p></div><a class="button-primary" href="{{ route('kasir') }}">Transaksi baru</a></div>
    <livewire:sales.transaction-history />
@endsection
