@extends('layouts.app')
@section('page-title', 'Pengeluaran')
@section('content')
    <div class="welcome-row page-intro"><div><h2>Pengeluaran toko</h2><p>Catat biaya operasional agar ringkasan laba bersih menggunakan data biaya yang tersedia.</p></div></div>
    <livewire:finance.manage-expenses />
@endsection
