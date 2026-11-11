@extends('layouts.app')
@section('page-title', 'Catatan Pesanan')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Catatan Pesanan</h2><p>Kelola spesifikasi, proses produksi, dan penyerahan barang.</p></div></div>
    <livewire:orders.manage-custom-orders />
@endsection
