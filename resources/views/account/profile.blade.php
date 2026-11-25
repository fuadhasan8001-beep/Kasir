@extends('layouts.app')
@section('page-title', 'Profil saya')
@section('content')
    <div class="profile-page">
        <header class="operations-hero profile-hero page-intro">
            <div class="operations-hero-copy">
                <span class="operations-eyebrow">Pengaturan akun</span>
                <h2>Profil &amp; keamanan</h2>
                <p>Kelola informasi akun dan kata sandi yang digunakan untuk masuk.</p>
            </div>
        </header>

        @if (session('status'))
            <div class="notice-card profile-status-message"><strong>{{ session('status') }}</strong></div>
        @endif

        <div class="profile-layout">
            <section class="catalog-panel profile-card profile-identity-card">
                <div class="profile-identity-header">
                    <span class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <div>
                        <span class="profile-section-eyebrow">Akun aktif</span>
                        <h2 class="profile-account-name">{{ $user->name }}</h2>
                        <p>Administrator toko</p>
                    </div>
                </div>

                <dl class="profile-account-details">
                    <div><dt>Email</dt><dd class="profile-account-value">{{ $user->email }}</dd></div>
                    <div><dt>Bergabung sejak</dt><dd>{{ $user->created_at->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}</dd></div>
                </dl>

                <div class="profile-logout-panel">
                    <div><strong>Keluar dari akun</strong><small>Akhiri sesi pada perangkat ini.</small></div>
                    <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Yakin mau keluar?')">
                        @csrf
                        <button class="button-danger profile-logout-button" type="submit">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M10 17l5-5-5-5M15 12H3m9-8h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6"/></svg>
                            Keluar akun
                        </button>
                    </form>
                </div>
            </section>

            <section class="catalog-panel profile-card profile-info-card">
                <div class="catalog-panel-heading">
                    <div><h2>Informasi profil</h2><p>Nama dan email yang ditampilkan pada akun Anda.</p></div>
                    <span class="profile-step-badge">01</span>
                </div>
                <form method="POST" action="{{ route('profile.update') }}" class="catalog-form profile-form">
                    @csrf
                    @method('PUT')
                    <label>Nama<input name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</label>
                    <label>Email<input name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label>
                    <div><button class="button-primary" type="submit">Simpan profil</button></div>
                </form>
            </section>

            <section class="catalog-panel profile-card profile-password-card" id="password">
                <div class="catalog-panel-heading">
                    <div><h2>Ganti kata sandi</h2><p>Gunakan minimal 8 karakter dan jangan bagikan kata sandi Anda.</p></div>
                    <span class="profile-step-badge">02</span>
                </div>
                <form method="POST" action="{{ route('profile.password') }}" class="catalog-form profile-form profile-password-form">
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
