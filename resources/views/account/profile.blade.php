@extends('layouts.app')
@section('page-title', 'Profil saya')
@section('content')
    <div class="welcome-row page-intro">
        <div>
            <h2>Profil akun</h2>
            <p>Perbarui informasi profil dan kata sandi akun yang sedang digunakan.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="catalog-panel mb-5 text-sm text-neutral-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(240px,0.7fr)_minmax(0,1.3fr)]">
        <section class="catalog-panel h-fit">
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-full bg-neutral-100 text-xl font-semibold text-neutral-800">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">{{ $user->name }}</h2>
                    <p class="mt-1 text-sm text-neutral-500">Admin (Kasir)</p>
                </div>
            </div>
            <dl class="mt-5 grid gap-3 border-t border-neutral-100 pt-4 text-sm">
                <div><dt class="text-xs text-neutral-500">Email</dt><dd class="mt-1 font-medium text-neutral-800">{{ $user->email }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Bergabung</dt><dd class="mt-1 font-medium text-neutral-800">{{ $user->created_at->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}</dd></div>
            </dl>
        </section>

        <div class="grid gap-5">
            <section class="catalog-panel">
                <div class="catalog-panel-heading">
                    <div><h2>Informasi profil</h2><p>Nama dan email untuk akun Anda.</p></div>
                </div>
<form method="POST" action="{{ route('profile.update') }}" class="catalog-form">
                    @csrf
                    @method('PUT')
                    <label>Nama<input name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</label>
                    <label>Email<input name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label>
                    <div><button class="button-primary" type="submit">Simpan profil</button></div>
                </form>
            </section>

            <section class="catalog-panel">
                <div class="catalog-panel-heading">
                    <div><h2>Ganti kata sandi</h2><p>Gunakan minimal 8 karakter dan jangan bagikan kata sandi Anda.</p></div>
                </div>
                <form method="POST" action="{{ route('profile.password') }}" class="catalog-form">
                    @csrf
                    @method('PUT')
                    <label>Kata sandi saat ini<input name="current_password" type="password" autocomplete="current-password" required>@error('current_password')<small>{{ $message }}</small>@enderror</label>
                    <label>Kata sandi baru<input name="password" type="password" autocomplete="new-password" minlength="8" required>@error('password')<small>{{ $message }}</small>@enderror</label>
                    <label>Konfirmasi kata sandi baru<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label>
                    <div><button class="button-primary" type="submit">Perbarui kata sandi</button></div>
                </form>
            </section>
        </div>
    </div>
@endsection
