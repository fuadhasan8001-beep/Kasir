<div>
    <!-- People find pleasure in different ways. I find it in keeping my mind clear. - Marcus Aurelius -->
</div>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Atur Kata Sandi · Gunawan Jaya Steel</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
</head>
<body class="login-page login-recovery-page">
    <main class="login-shell login-recovery-shell">
        <section class="login-panel">
            <div class="login-panel-inner">
                <a class="login-mobile-brand" href="{{ route('login') }}" aria-label="Gunawan Jaya Steel">
                    <img src="{{ asset('images/logo-gjs.png') }}" alt="" width="64" height="64">
                    <span><strong>Gunawan Jaya Steel</strong><small>Material &amp; konstruksi</small></span>
                </a>
                <span class="login-eyebrow"><span aria-hidden="true"></span> PEMULIHAN AKUN</span>
                <h1>Atur kata sandi baru</h1>
                <p class="login-description">Buat kata sandi baru untuk akun Anda.</p>

                <form method="POST" action="{{ route('password.update') }}" class="login-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="login-field">
                        <label for="email">Alamat email</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" placeholder="nama@toko.id" required autocomplete="username" @error('email') class="is-invalid" aria-invalid="true" aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')<p class="login-error" id="email-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="login-field">
                        <label for="password">Kata sandi baru</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                            <input id="password" type="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" @error('password') class="is-invalid" aria-invalid="true" aria-describedby="password-error" @enderror>
                        </div>
                        @error('password')<p class="login-error" id="password-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="login-field">
                        <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi kata sandi baru" required autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="login-submit"><span>Simpan kata sandi baru</span><span aria-hidden="true">↗</span></button>
                </form>

                <a class="login-forgot-password" href="{{ route('login') }}">Kembali ke halaman masuk</a>
            </div>
        </section>
    </main>
</body>
</html>
