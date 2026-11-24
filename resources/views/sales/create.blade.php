@extends('layouts.app')
@section('page-title', request()->routeIs('orders.custom') ? 'Pesanan' : 'Kasir')
@section('content')
    @php($showOrders = request()->routeIs('orders.custom'))
    <div class="welcome-row page-intro"><div><h2>{{ $showOrders ? 'Pesanan' : 'Kasir' }}</h2><p>{{ $showOrders ? 'Pantau proses pesanan dan selesaikan pembayaran saat barang diserahkan.' : 'Pilih barang, masukkan jumlah, lalu selesaikan pembayaran.' }}</p></div></div>
    @if($showOrders)
        <livewire:orders.manage-custom-orders />
    @else
        <livewire:sales.point-of-sale />
    @endif
@endsection
