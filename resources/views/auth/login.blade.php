<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · Gunawan Jaya Steel</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
</head>
<body class="login-page">
    <main class="login-shell">
        <aside class="login-showcase" aria-label="Gunawan Jaya Steel">
            <a class="login-brand" href="{{ route('login') }}" aria-label="Gunawan Jaya Steel">
                <img src="{{ asset('images/gjs-logo.svg') }}" alt="" width="48" height="48">
                <span><strong>Gunawan Jaya Steel</strong><small>Material & konstruksi</small></span>
            </a>

            <div class="login-showcase-copy">
                <span class="login-overline"><span></span> SISTEM OPERASIONAL TOKO</span>
                <h2>Semua pekerjaan toko, <em>lebih tertata.</em></h2>
                <p>Kelola penjualan, persediaan, dan pesanan custom dari satu ruang kerja.</p>
            </div>

            <div class="login-blueprint" aria-hidden="true">
                <div class="blueprint-ring blueprint-ring--outer"></div>
                <div class="blueprint-ring blueprint-ring--inner"></div>
                <div class="blueprint-axis blueprint-axis--horizontal"></div>
                <div class="blueprint-axis blueprint-axis--vertical"></div>
                <div class="blueprint-core"><span>GJS</span><small>EST. · MATERIAL</small></div>
                <span class="blueprint-label blueprint-label--top">BESI · BAJA</span>
                <span class="blueprint-label blueprint-label--bottom">PRESISI UNTUK SETIAP PROYEK</span>
            </div>

            <div class="login-showcase-footer"><span>KUAT DALAM MATERIAL.</span><span>RAPI DALAM PENGELOLAAN.</span></div>
        </aside>

        <section class="login-panel">
            <div class="login-panel-inner">
                <div class="login-mobile-brand">
                    <img src="{{ asset('images/gjs-logo.svg') }}" alt="" width="42" height="42">
                    <span><strong>Gunawan Jaya Steel</strong><small>Material & konstruksi</small></span>
                </div>
                <span class="login-eyebrow">SELAMAT DATANG KEMBALI</span>
                <h1>Masuk ke akun Anda</h1>
                <p class="login-description">Gunakan akun terdaftar untuk membuka ruang kerja toko.</p>

                <form method="POST" action="{{ route('login') }}" id="login-form" class="login-form">
                    @csrf
                    <div class="login-field">
                        <label for="email">Alamat email</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@toko.id" required autofocus autocomplete="username" @error('email') class="is-invalid" aria-invalid="true" aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')<p class="login-error" id="email-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="login-field">
                        <label for="password">Kata sandi</label>
                        <div class="login-input-wrap login-password-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                            <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password" @error('password') class="is-invalid" aria-invalid="true" aria-describedby="password-error" @enderror>
                            <button type="button" class="password-toggle" id="toggle" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                <svg class="eye" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.3 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 3 18 18M10.6 5.6A9.5 9.5 0 0 1 12 5.5c6.2 0 9.5 6.5 9.5 6.5a15 15 0 0 1-3.1 3.8M6.2 6.2A15.4 15.4 0 0 0 2.5 12s3.3 6.5 9.5 6.5a9.5 9.5 0 0 0 4-.9"/></svg>
                            </button>
                        </div>
                        @error('password')<p class="login-error" id="password-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="login-submit" id="submit"><span>Masuk ke ruang kerja</span><span aria-hidden="true">↗</span></button>
                </form>

                <p class="login-security"><span aria-hidden="true">◈</span> Akses khusus untuk pengguna terdaftar.</p>
            </div>
            <footer class="login-panel-footer"><span>GJS · OPERASIONAL</span><span>{{ date('Y') }} Gunawan Jaya Steel</span></footer>
        </section>
    </main>

    <script>
        (function () {
            var input = document.getElementById('password');
            var toggle = document.getElementById('toggle');
            var form = document.getElementById('login-form');
            var submit = document.getElementById('submit');

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.classList.toggle('is-visible', show);
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            });

            form.addEventListener('submit', function () {
                submit.disabled = true;
                submit.querySelector('span').textContent = 'Memproses...';
            });

            window.addEventListener('pageshow', function () {
                submit.disabled = false;
                submit.querySelector('span').textContent = 'Masuk ke ruang kerja';
            });
        })();
    </script>
</body>
</html>
