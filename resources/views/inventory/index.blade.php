@extends('layouts.app')
@section('page-title', 'Inventaris')
@section('content')
    <header class="operations-hero page-intro"><div class="operations-hero-copy"><span class="operations-eyebrow">Stok &amp; persediaan</span><h2>Inventaris toko</h2><p>Periksa stok barang jadi dan catat setiap perubahan persediaan.</p></div><a class="button-primary operations-hero-button" href="#catat-stok">Catat perubahan</a></header>
    <livewire:inventory.manage-inventory />
@endsection
