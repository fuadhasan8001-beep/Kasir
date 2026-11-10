@extends('layouts.app')
@section('page-title', 'Catatan stok')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Catatan stok</h2><p>Catat barang masuk dan keluar, serta pantau stok yang menipis atau habis.</p></div></div>
    <livewire:inventory.manage-inventory />
@endsection
