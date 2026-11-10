<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman tidak ditemukan · GJS</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
</head>
<body class="login-page">
    <main class="login-card">
        <img class="brand-logo-large" src="{{ asset('images/gjs-logo.svg') }}" alt="Gunawan Jaya Steel">
        <p class="eyebrow">404</p>
        <h1>Halaman tidak ditemukan</h1>
        <p class="login-intro">Alamat yang Anda buka tidak tersedia atau sudah berubah.</p>
        <a class="primary-button" href="{{ route('kasir') }}">Kembali ke Kasir</a>
    </main>
</body>
</html>
