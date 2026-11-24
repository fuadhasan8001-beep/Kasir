<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Kata Sandi · Gunawan Jaya Steel</title>
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
                <h1>Lupa kata sandi?</h1>
                <p class="login-description">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur kata sandi baru.</p>

                @if (session('status'))
                    <p class="login-status" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="login-form">
                    @csrf
                    <div class="login-field">
                        <label for="email">Alamat email</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@toko.id" required autofocus autocomplete="username" @error('email') class="is-invalid" aria-invalid="true" aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')<p class="login-error" id="email-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="login-submit"><span>Kirim tautan reset</span><span aria-hidden="true">↗</span></button>
                </form>

                <a class="login-forgot-password" href="{{ route('login') }}">Kembali ke halaman masuk</a>
            </div>
        </section>
    </main>
</body>
</html>
