@extends('layouts.app')
@section('page-title', 'Pesanan custom')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Pesanan produksi custom</h2><p>Kelola pesanan, pembayaran bertahap, proses produksi, dan penyerahan barang.</p></div></div>
    <livewire:orders.manage-custom-orders />
@endsection
