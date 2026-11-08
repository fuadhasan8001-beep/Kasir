@extends('layouts.app')
@section('page-title', 'Ubah akun kasir')
@section('content')
    <div class="welcome-row page-intro">
        <div>
            <h2>Ubah akun kasir</h2>
            <p>Perbarui identitas atau atur ulang kata sandi akun kasir.</p>
        </div>
        <a class="button-secondary" href="{{ route('cashiers.index') }}">Kembali ke daftar</a>
    </div>

    <section class="catalog-panel max-w-2xl">
        <div class="catalog-panel-heading">
            <div><h2>{{ $cashier->name }}</h2><p>Role akun: Kasir</p></div>
        </div>
        <form method="POST" action="{{ route('cashiers.update', $cashier) }}" class="catalog-form">
            @csrf
            @method('PUT')
            <label>Nama<input name="name" type="text" value="{{ old('name', $cashier->name) }}" maxlength="255" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</label>
            <label>Email<input name="email" type="email" value="{{ old('email', $cashier->email) }}" maxlength="255" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label>
            <label>Kata sandi baru <span class="font-normal text-neutral-500">(kosongkan jika tidak diubah)</span><input name="password" type="password" autocomplete="new-password" minlength="8">@error('password')<small>{{ $message }}</small>@enderror</label>
            <label>Konfirmasi kata sandi baru<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8"></label>
            <div class="flex flex-wrap gap-2">
                <button class="button-primary" type="submit">Simpan perubahan</button>
                <a class="button-secondary" href="{{ route('cashiers.index') }}">Batal</a>
            </div>
        </form>
    </section>
@endsection
