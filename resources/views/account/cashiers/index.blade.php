@extends('layouts.app')
@section('page-title', 'Kelola kasir')
@section('content')
    <div class="welcome-row page-intro">
        <div>
            <h2>Akun kasir</h2>
            <p>Buat akun, perbarui informasi, atau hapus akun kasir toko.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="catalog-panel mb-5 text-sm text-neutral-800">{{ session('status') }}</div>
    @endif
    @error('cashier')
        <div class="catalog-panel mb-5 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-5 xl:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
        <section class="catalog-panel h-fit">
            <div class="catalog-panel-heading">
                <div><h2>Tambah akun kasir</h2><p>Akun baru otomatis menggunakan role Kasir.</p></div>
            </div>
            <form method="POST" action="{{ route('cashiers.store') }}" class="catalog-form">
                @csrf
                <label>Nama<input name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</label>
                <label>Email<input name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label>
                <label>Kata sandi<input name="password" type="password" autocomplete="new-password" minlength="8" required>@error('password')<small>{{ $message }}</small>@enderror</label>
                <label>Konfirmasi kata sandi<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label>
                <div><button class="button-primary" type="submit">Buat akun kasir</button></div>
            </form>
        </section>

        <section class="catalog-panel">
            <div class="catalog-panel-heading">
                <div><h2>Daftar kasir</h2><p>{{ $cashiers->total() }} akun kasir terdaftar.</p></div>
            </div>
            <div class="table-scroll">
                <table class="catalog-table">
                    <thead><tr><th>Nama</th><th>Email</th><th>Bergabung</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($cashiers as $cashier)
                            <tr>
                                <td class="font-medium text-neutral-900">{{ $cashier->name }}</td>
                                <td>{{ $cashier->email }}</td>
                                <td>{{ $cashier->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y') }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <a class="button-secondary button-small" href="{{ route('cashiers.edit', $cashier) }}">Ubah</a>
                                        <form method="POST" action="{{ route('cashiers.destroy', $cashier) }}" onsubmit="return confirm('Yakin ingin menghapus akun kasir ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button-danger button-small" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-neutral-500">Belum ada akun kasir.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $cashiers->links() }}</div>
        </section>
    </div>
@endsection
