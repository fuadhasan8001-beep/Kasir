@extends('layouts.app')
@section('page-title', 'Kasir')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Catat transaksi</h2><p>Pilih barang dan masukkan jumlah atau ukuran. Penyimpanan catatan akan memperbarui stok.</p></div></div>
    <livewire:sales.point-of-sale />
@endsection
