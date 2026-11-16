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
                <img src="{{ asset('images/logo-gjs.png') }}" alt="Logo Gunawan Jaya Steel" width="72" height="72">
                <span><strong>Gunawan Jaya Steel</strong><small>Material & konstruksi</small></span>
            </a>

            <div class="login-showcase-content">
                <div class="login-showcase-copy">
                    <span class="login-overline"><span></span> SISTEM OPERASIONAL TOKO</span>
                    <h2 class="login-headline">
                        <span class="login-headline-line">Semua pekerjaan</span>
                        <span class="login-headline-line"><span class="login-headline-light">toko,</span> <em>lebih tertata.</em></span>
                    </h2>
                    <p>Kelola penjualan, persediaan, dan pesanan custom dengan alur kerja yang lebih rapi dalam satu ruang kerja.</p>
                </div>

                <div class="login-material-card" aria-label="Material besi dan baja: besi lonjoran, begel, cakar ayam, slop, kawat, dan wermes">
                    <h3 class="login-material-title"><span></span> MATERIAL BESI &amp; BAJA</h3>
                    <div class="login-material-products" aria-hidden="true">
                        <div class="login-material-product">
                            <svg class="material-illustration material-rods" viewBox="0 0 180 92" fill="none">
                                <path d="M24 68 143 25M31 76 150 33M38 83 157 40M18 60 137 17" />
                                <path class="material-highlight" d="m26 66 119-43m-114 50L150 30m-105 50L164 37" />
                                <path class="material-cap" d="m18 60 6 8m1 0 6 8m1 0 6 7m1-23 6 8m1 0 6 8m1 0 6 7" />
                            </svg>
                            <span>Besi lonjoran</span>
                        </div>
                        <div class="login-material-product">
                            <svg class="material-illustration material-stirrup" viewBox="0 0 120 92" fill="none">
                                <path d="M38 17h43a9 9 0 0 1 9 9v42a9 9 0 0 1-9 9H39a9 9 0 0 1-9-9V29" />
                                <path class="material-highlight" d="M39 22h40a8 8 0 0 1 8 8v36a7 7 0 0 1-7 7H40a6 6 0 0 1-6-6V29" />
                                <path class="material-end" d="m30 29 1-9 8-3m-9 12 9-2" />
                                <path class="material-tie" d="M37 20v7m45-6v7M36 70h7m38 0h7" />
                            </svg>
                            <span>Begel</span>
                        </div>
                        <div class="login-material-product">
                            <svg class="material-illustration material-footing" viewBox="0 0 150 92" fill="none">
                                <path d="m31 27 44-16 44 16-44 17-44-17Z" />
                                <path d="M31 27v39l44 17V44M119 27v39L75 83" />
                                <path class="material-highlight" d="m39 30 36-13 36 13-36 14-36-14Zm0 7v25m12-20v30m12-35v39m24-39v39m12-44v34m12-38v28" />
                                <path class="material-cross" d="m31 44 44 17 44-17M31 56l44 17 44-17" />
                                <path class="material-accent" d="m75 12 44 16m-44 16v39" />
                            </svg>
                            <span>Cakar ayam</span>
                        </div>
                        <div class="login-material-product">
                            <svg class="material-illustration material-slop" viewBox="0 0 120 76" fill="none">
                                <path d="m20 27 45-16 34 13-45 17-34-14Z" />
                                <path d="M20 27v17l34 14V41m45-17v17L54 58" />
                                <path class="material-highlight" d="m30 27 35-12 22 9-35 13-22-10Z" />
                                <path class="material-accent" d="m20 44 34 14 45-17" />
                                <path class="material-end" d="m65 15 22 9v13" />
                            </svg>
                            <span>Slop</span>
                        </div>
                        <div class="login-material-product">
                            <svg class="material-illustration material-wire" viewBox="0 0 120 76" fill="none">
                                <path d="M32 25c0-8 12-14 27-14s27 6 27 14-12 14-27 14-27-6-27-14Z" />
                                <path d="M32 25v19c0 8 12 14 27 14s27-6 27-14V25" />
                                <path class="material-highlight" d="M38 25c0-5 9-9 21-9s21 4 21 9-9 9-21 9-21-4-21-9Zm0 2v17c0 5 9 9 21 9s21-4 21-9V27" />
                                <path class="material-accent" d="M32 35c0 8 12 14 27 14s27-6 27-14" />
                                <path class="material-end" d="m86 44 12 6-8 7-12-6" />
                            </svg>
                            <span>Kawat</span>
                        </div>
                        <div class="login-material-product">
                            <svg class="material-illustration material-wermes" viewBox="0 0 120 76" fill="none">
                                <path d="m22 21 47-11 30 12-48 12-29-13Z" />
                                <path d="M22 21v30l29 15V34m48-12v29L51 66" />
                                <path class="material-highlight" d="m31 23 39-9m-28 17 40-10m-28 21 40-10m-52-4 29 15m-17-19 29 15m-17-19 29 15m-52 4 29 15m-17-19 29 15m-17-19 29 15" />
                                <path class="material-accent" d="m22 36 29 15 48-13m-48-4 48-12" />
                                <path class="material-end" d="m69 10 30 12v29" />
                            </svg>
                            <span>Wermes</span>
                        </div>
                    </div>
                </div>

                <div class="login-showcase-footer"><span>KUAT DALAM KONSTRUKSI.</span><span>STOK BESI LEBIH TERKONTROL.</span></div>
            </div>
        </aside>

        <section class="login-panel">
            <div class="login-panel-inner">
                <div class="login-mobile-brand">
                    <img src="{{ asset('images/logo-gjs.png') }}" alt="Logo Gunawan Jaya Steel" width="56" height="56">
                    <span><strong>Gunawan Jaya Steel</strong><small>Material & konstruksi</small></span>
                </div>
                <span class="login-eyebrow"><span aria-hidden="true"></span> SELAMAT DATANG KEMBALI</span>
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

                @if (Route::has('password.request'))
                    <a class="login-forgot-password" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                @endif

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
