@extends('layouts.app')
@section('page-title', 'Transaksi')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Riwayat transaksi</h2><p>Telusuri transaksi dan rincian pembayaran yang tersimpan.</p></div><a class="button-primary" href="{{ route('sales.create') }}">Transaksi baru</a></div>
    <livewire:sales.transaction-history />
@endsection
