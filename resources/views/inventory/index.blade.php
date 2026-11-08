@extends('layouts.app')
@section('page-title', 'Inventaris')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Persediaan toko</h2><p>Catat barang masuk, barang keluar, dan hasil pengecekan fisik.</p></div></div>
    <livewire:inventory.manage-inventory />
@endsection
