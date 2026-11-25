@extends('layouts.app')
@section('page-title', request()->routeIs('orders.custom') ? 'Pesanan' : 'Kasir')
@section('content')
    @php($showOrders = request()->routeIs('orders.custom'))
    @if($showOrders)
        <header class="operations-hero page-intro"><div class="operations-hero-copy"><span class="operations-eyebrow">Pemesanan custom</span><h2>Pesanan pelanggan</h2><p>Catat kebutuhan khusus, pantau produksi, dan kelola jadwal pengambilan.</p></div><a class="button-primary operations-hero-button" href="#buat-pesanan">Buat pesanan</a></header>
        <livewire:orders.manage-custom-orders />
    @else
        <livewire:sales.point-of-sale />
    @endif
@endsection
