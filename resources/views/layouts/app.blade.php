<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Gunawan Jaya Steel' }} · GJS</title>
    @vite(['resources/css/app.css', 'resources/css/catalog.css'])
    @livewireStyles
</head>
<body class="app-shell">
    <div x-data="{
    mobileMenuOpen: false,
    accountSheetOpen: false,
    isMobile() {
        return window.innerWidth <= 640;
    },
    pageTitle() {
        return '{{ $title ?? "Gunawan Jaya Steel" }}';
    }
}" x-on:resize.window="
    if (mobileMenuOpen) mobileMenuOpen = false;
    if (accountSheetOpen) accountSheetOpen = false;
" x-on:keydown.escape.window="mobileMenuOpen = false; accountSheetOpen = false">
        <!-- Mobile topbar: visible only on mobile, shows logo + page title -->
        <header class="topbar mobile-topbar" x-show="isMobile()">
            <button class="bottom-nav-toggle" type="button" aria-label="Buka menu navigasi" x-on:click="mobileMenuOpen = true">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-brand">
                <img class="brand-logo" src="{{ asset('images/gjs-logo.svg') }}" alt="Gunawan Jaya Steel" />
                <span class="brand-name">Gunawan Jaya Steel</span>
            </div>
            <h1 class="title" x-text="pageTitle"></h1>
        </header>

        <!-- Desktop sidebar (shown on desktop) -->
        <aside class="sidebar" x-bind:class="{ 'open': mobileMenuOpen }">
            <a class="brand" href="{{ route('kasir') }}">
                <img class="brand-logo" src="{{ asset('images/gjs-logo.svg') }}" alt="Gunawan Jaya Steel" />
                <strong class="brand-name">Gunawan Jaya Steel</strong>
            </a>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <div class="nav-caption">MENU UTAMA</div>
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span aria-hidden="true">▦</span> Dashboard</a>
                <a class="nav-item {{ request()->routeIs('kasir') ? 'active' : '' }}" href="{{ route('kasir') }}">
                    <span aria-hidden="true">▤</span> Kasir
                </a>
                <a class="nav-item {{ request()->routeIs('catatan') ? 'active' : '' }}" href="{{ route('catatan') }}">
                    <span aria-hidden="true">◉</span> Catatan
                </a>
                <a class="nav-item {{ request()->routeIs('riwayat') ? 'active' : '' }}" href="{{ route('riwayat') }}">
                    <span aria-hidden="true">⏱</span> Riwayat Penjualan
                </a>
                <a class="nav-item {{ request()->routeIs('orders.custom') ? 'active' : '' }}" href="{{ route('orders.custom') }}"><span aria-hidden="true">⌑</span> Catatan Pesanan</a>
                <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span aria-hidden="true">▤</span> Data barang</a>
                <a class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span aria-hidden="true">⇄</span> Inventaris</a>
            </nav>
            <div class="sidebar-bottom">
                <div class="user-chip">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="user-chip-detail">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>Admin (Kasir)</small>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="logout-form" onsubmit="return confirm('Yakin ingin keluar?')">
                    @csrf
                    <button type="submit" class="logout">Keluar</button>
                </form>
            </div>
        </aside>

        <!-- Mobile drawer backdrop -->
        <button class="sidebar-backdrop" type="button" aria-label="Tutup menu navigasi" x-show="mobileMenuOpen" x-on:click="mobileMenuOpen = false"></button>

        <main class="main-area">
            <section class="content">
                @yield('content')
            </section>
        </main>

        <!-- Mobile bottom navigation -->
        <nav class="bottom-nav" x-show="isMobile()">
            <a class="bottom-nav-item {{ request()->routeIs('kasir') ? 'active' : '' }}" href="{{ route('kasir') }}">
                <div class="nav-icon">▤</div>
                <span>Kasir</span>
            </a>
            <a class="bottom-nav-item {{ request()->routeIs('catatan') ? 'active' : '' }}" href="{{ route('catatan') }}">
                <div class="nav-icon">◉</div>
                <span>Catatan</span>
            </a>
            <a class="bottom-nav-item {{ request()->routeIs('riwayat') ? 'active' : '' }}" href="{{ route('riwayat') }}">
                <div class="nav-icon">⏱</div>
                <span>Riwayat</span>
            </a>
            <a class="bottom-nav-item bottom-nav-account" href="#" @click="accountSheetOpen = true">
                <div class="avatar avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <span>Akun</span>
            </a>
        </nav>

        <!-- Mobile account bottom sheet -->
        <div class="modal-sheet" x-show="accountSheetOpen" @keydown.prevent.escape="accountSheetOpen = false" tabindex="-1" role="dialog" aria-modal="true" aria-label="Profil dan keluar">
            <div class="modal-sheet-overlay" @click="accountSheetOpen = false"></div>
            <div class="modal-sheet-panel">
                <div class="sheet-heading">
                    <div class="sheet-user-row">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <div>
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>Admin (Kasir)</small>
                        </div>
                    </div>
                    <a class="close-sheet" href="#" @click.prevent="accountSheetOpen = false">✕</a>
                </div>
<div class="sheet-body">
                    <p class="muted">Kelola profil akun admin.</p>
                    <a class="link-secondary" href="{{ route('profile.edit') }}">→ Profil</a>
                    <form method="POST" action="{{ route('logout') }}" class="logout-row">
                        @csrf
                        <button type="submit" onclick="return confirm('Yakin ingin keluar?')">Keluar</button>
                    </form>
                </div>
            </div>
        </div>

        @livewireScripts
    </div>
</body>
</html>
