<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · TokoBangunan POS</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
    @livewireStyles
</head>
<body class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark">TB</span>
            <span><strong>TokoBangunan</strong><small>POS</small></span>
        </a>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <div class="nav-caption">MENU UTAMA</div>
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span aria-hidden="true">▦</span> Dashboard</a>
            @if (auth()->user()->hasPermission('sales.create'))
                <a class="nav-item {{ request()->routeIs('sales.create') ? 'active' : '' }}" href="{{ route('sales.create') }}"><span aria-hidden="true">▣</span> Kasir</a>
                <a class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.custom') }}"><span aria-hidden="true">◷</span> Pesanan custom</a>
            @endif
            @if (auth()->user()->hasPermission('sales.create') || auth()->user()->hasPermission('sales.view_all'))
                <a class="nav-item {{ request()->routeIs('sales.index') ? 'active' : '' }}" href="{{ route('sales.index') }}"><span aria-hidden="true">▤</span> Transaksi</a>
            @endif
            @if (auth()->user()->hasPermission('inventory.manage'))
                <a class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span aria-hidden="true">⇄</span> Inventaris</a>
            @endif
            @if (auth()->user()->hasPermission('finance.manage'))
                <a class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span aria-hidden="true">−</span> Pengeluaran</a>
            @endif
            @if (auth()->user()->hasPermission('catalog.view'))
                <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span aria-hidden="true">▤</span> Barang</a>
            @endif
            @if (auth()->user()->hasPermission('catalog.manage'))
                <a class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><span aria-hidden="true">▧</span> Kategori</a>
                <a class="nav-item {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><span aria-hidden="true">◫</span> Satuan</a>
            @endif
            @if (auth()->user()->hasPermission('users.manage'))
                <a class="nav-item {{ request()->routeIs('cashiers.*') ? 'active' : '' }}" href="{{ route('cashiers.index') }}"><span aria-hidden="true">♙</span> Kelola kasir</a>
            @endif
            <a class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><span aria-hidden="true">◉</span> Profil saya</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-chip">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->label ?? 'Pengguna' }}</small></span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">Keluar</button>
            </form>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <div><span class="eyebrow">TOKOBANGUNAN POS</span><h1>@yield('page-title', 'Dashboard')</h1></div>
            <div class="topbar-meta"><span class="status-dot"></span> Sistem siap <span class="date-label">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span></div>
        </header>
        <section class="content">@yield('content')</section>
    </main>
    @livewireScripts
</body>
</html>
