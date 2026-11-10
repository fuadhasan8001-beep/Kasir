<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesi berakhir · GJS</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
</head>
<body class="login-page">
    <main class="login-card">
        <p class="eyebrow">419</p>
        <h1>Sesi telah berakhir</h1>
        <p class="login-intro">Silakan masuk kembali untuk melanjutkan.</p>
        <a class="primary-button" href="{{ route('login') }}">Kembali ke login</a>
    </main>
</body>
</html>
