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
                    <label>Kata sandi saat ini
                        <div class="profile-password-input-wrap" x-data="{ visible: false }">
                            <input name="current_password" x-bind:type="visible ? 'text' : 'password'" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" x-on:click="visible = ! visible" x-bind:class="{ 'is-visible': visible }" x-bind:aria-pressed="visible.toString()" x-bind:aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                <svg class="eye" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.3 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 3 18 18M10.6 5.6A9.5 9.5 0 0 1 12 5.5c6.2 0 9.5 6.5 9.5 6.5a15 15 0 0 1-3.1 3.8M6.2 6.2A15.4 15.4 0 0 0 2.5 12s3.3 6.5 9.5 6.5a9.5 9.5 0 0 0 4-.9"/></svg>
                            </button>
                        </div>
                        @error('current_password')<small>{{ $message }}</small>@enderror
                    </label>
                    <label>Kata sandi baru
                        <div class="profile-password-input-wrap" x-data="{ visible: false }">
                            <input name="password" x-bind:type="visible ? 'text' : 'password'" autocomplete="new-password" minlength="8" required>
                            <button class="password-toggle" type="button" x-on:click="visible = ! visible" x-bind:class="{ 'is-visible': visible }" x-bind:aria-pressed="visible.toString()" x-bind:aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                <svg class="eye" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.3 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 3 18 18M10.6 5.6A9.5 9.5 0 0 1 12 5.5c6.2 0 9.5 6.5 9.5 6.5a15 15 0 0 1-3.1 3.8M6.2 6.2A15.4 15.4 0 0 0 2.5 12s3.3 6.5 9.5 6.5a9.5 9.5 0 0 0 4-.9"/></svg>
                            </button>
                        </div>
                        @error('password')<small>{{ $message }}</small>@enderror
                    </label>
                    <label>Konfirmasi kata sandi baru
                        <div class="profile-password-input-wrap profile-password-input-wrap-no-toggle">
                            <input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                            <button class="password-toggle" type="button" x-on:click="visible = ! visible" x-bind:class="{ 'is-visible': visible }" x-bind:aria-pressed="visible.toString()" x-bind:aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                <svg class="eye" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.3 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 3 18 18M10.6 5.6A9.5 9.5 0 0 1 12 5.5c6.2 0 9.5 6.5 9.5 6.5a15 15 0 0 1-3.1 3.8M6.2 6.2A15.4 15.4 0 0 0 2.5 12s3.3 6.5 9.5 6.5a9.5 9.5 0 0 0 4-.9"/></svg>
                            </button>
                        </div>
                    </label>
                    <div><button class="button-primary" type="submit">Perbarui kata sandi</button></div>
                </form>
            </section>
        </div>
    </div>
@endsection
