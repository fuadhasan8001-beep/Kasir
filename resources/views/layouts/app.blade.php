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
        return window.innerWidth <= 1023;
    },
}" x-on:resize.window="
    if (mobileMenuOpen) mobileMenuOpen = false;
    if (accountSheetOpen) accountSheetOpen = false;
" x-on:keydown.escape.window="mobileMenuOpen = false; accountSheetOpen = false">
        <!-- Mobile topbar: visible only on mobile, shows logo + page title -->
        <header class="topbar topbar-mobile" x-show="isMobile()">
            <button class="mobile-menu-toggle" type="button" aria-label="Buka menu navigasi" x-on:click="mobileMenuOpen = true">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-brand">
                <img class="brand-logo" src="{{ asset('images/gjs-logo.svg') }}" alt="Gunawan Jaya Steel" />
                <span class="brand-name">Gunawan Jaya Steel</span>
            </div>
            <h1 class="title">@yield('page-title', 'Kasir')</h1>
        </header>

        <!-- Desktop sidebar (shown on desktop) -->
        <aside class="sidebar" x-bind:class="{ 'open': mobileMenuOpen }">
            <a class="brand" href="{{ route('kasir') }}">
                <img class="brand-logo" src="{{ asset('images/gjs-logo.svg') }}" alt="Gunawan Jaya Steel" />
                <strong class="brand-name">Gunawan Jaya Steel</strong>
            </a>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <div class="nav-caption">OPERASIONAL</div>
                <a class="nav-item {{ request()->routeIs('kasir', 'sales.create') ? 'active' : '' }}" href="{{ route('kasir') }}">
                    <span aria-hidden="true">▤</span> Kasir
                </a>
                <a class="nav-item {{ request()->routeIs('catatan', 'inventory.index') ? 'active' : '' }}" href="{{ route('catatan') }}">
                    <span aria-hidden="true">◉</span> Catatan
                </a>
                <a class="nav-item {{ request()->routeIs('riwayat', 'sales.index') ? 'active' : '' }}" href="{{ route('riwayat') }}">
                    <span aria-hidden="true">⏱</span> Riwayat Penjualan
                </a>
                <div class="nav-caption">KATALOG & PENGELOLAAN</div>
                <a class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.custom') }}">
                    <span aria-hidden="true">⌁</span> Pesanan custom
                </a>
                <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <span aria-hidden="true">▧</span> Barang
                </a>
                <a class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <span aria-hidden="true">◫</span> Kategori
                </a>
                <a class="nav-item {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                    <span aria-hidden="true">▤</span> Satuan
                </a>
                <a class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                    <span aria-hidden="true">−</span> Pengeluaran
                </a>
                <a class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                    <span aria-hidden="true">◉</span> Profil
                </a>
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
            <header class="desktop-topbar">
                <div>
                    <p class="desktop-topbar-kicker">RUANG OPERASIONAL</p>
                    <h1>@yield('page-title', 'Kasir')</h1>
                </div>
                <div class="desktop-topbar-meta">
                    <span class="system-status"><span class="status-dot"></span> Sistem siap</span>
                    <span class="date-label">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span>
                    <a class="topbar-account" href="{{ route('profile.edit') }}" aria-label="Buka profil {{ auth()->user()->name }}">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </a>
                </div>
            </header>
            <section class="content">
                @yield('content')
                <footer class="app-footer">
                    <span>GJS <span aria-hidden="true">·</span> Ruang kerja toko</span>
                    <span>{{ now()->timezone('Asia/Jakarta')->format('Y') }} Gunawan Jaya Steel</span>
                </footer>
            </section>
        </main>

        <!-- Mobile bottom navigation -->
        <nav class="bottom-nav" x-show="isMobile()">
            <a class="bottom-nav-item {{ request()->routeIs('kasir', 'sales.create') ? 'active' : '' }}" href="{{ route('kasir') }}">
                <div class="nav-icon">▤</div>
                <span>Kasir</span>
            </a>
            <a class="bottom-nav-item {{ request()->routeIs('catatan', 'inventory.index') ? 'active' : '' }}" href="{{ route('catatan') }}">
                <div class="nav-icon">◉</div>
                <span>Catatan</span>
            </a>
            <a class="bottom-nav-item {{ request()->routeIs('riwayat', 'sales.index') ? 'active' : '' }}" href="{{ route('riwayat') }}">
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
                    <p class="muted">Hubungi pemilik sistem untuk reset kata sandi.</p>
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
