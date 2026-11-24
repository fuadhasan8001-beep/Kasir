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
    bodyScrollState: null,
    touchStartY: 0,
    scrollGuard: null,
    touchStartGuard: null,
    lockBodyScroll() {
        if (this.bodyScrollState !== null) {
            return;
        }

        const body = document.body;
        const root = document.documentElement;
        this.bodyScrollState = {
            scrollY: window.scrollY,
            position: body.style.position,
            top: body.style.top,
            left: body.style.left,
            right: body.style.right,
            width: body.style.width,
            overflow: body.style.overflow,
            overscrollBehavior: body.style.overscrollBehavior,
            rootOverflow: root.style.overflow,
            rootOverscrollBehavior: root.style.overscrollBehavior,
        };

        root.style.overflow = 'hidden';
        root.style.overscrollBehavior = 'none';
        body.style.position = 'fixed';
        body.style.top = `-${this.bodyScrollState.scrollY}px`;
        body.style.left = '0';
        body.style.right = '0';
        body.style.width = '100%';
        body.style.overflow = 'hidden';
        body.style.overscrollBehavior = 'none';
    },
    preventBackgroundScroll(event) {
        const sidebar = document.querySelector('.sidebar');
        const menu = sidebar?.querySelector('.sidebar-nav');

        if (!this.mobileMenuOpen || !sidebar || !menu) {
            return;
        }

        if (!sidebar.contains(event.target)) {
            event.preventDefault();
            return;
        }

        if (!menu.contains(event.target)) {
            event.preventDefault();
            return;
        }

        const currentTouch = event.type === 'touchmove' ? event.touches[0] : null;

        if (currentTouch) {
            const delta = this.touchStartY - currentTouch.clientY;
            const maximumScroll = menu.scrollHeight - menu.clientHeight;

            this.touchStartY = currentTouch.clientY;
            menu.scrollTop = Math.max(0, Math.min(maximumScroll, menu.scrollTop + delta));
            event.preventDefault();
            return;
        }

        const delta = event.deltaY;
        const maximumScroll = menu.scrollHeight - menu.clientHeight;

        if ((delta < 0 && menu.scrollTop <= 0) || (delta > 0 && menu.scrollTop >= maximumScroll - 1)) {
            event.preventDefault();
        }
    },
    updateScrollGuards(isOpen) {
        if (isOpen) {
            this.lockBodyScroll();
            this.scrollGuard = this.scrollGuard ?? ((event) => this.preventBackgroundScroll(event));
            this.touchStartGuard = this.touchStartGuard ?? ((event) => {
                if (event.touches.length > 0) {
                    this.touchStartY = event.touches[0].clientY;
                }
            });

            document.addEventListener('wheel', this.scrollGuard, { capture: true, passive: false });
            document.addEventListener('touchstart', this.touchStartGuard, { capture: true, passive: true });
            document.addEventListener('touchmove', this.scrollGuard, { capture: true, passive: false });
            return;
        }

        if (this.scrollGuard !== null) {
            document.removeEventListener('wheel', this.scrollGuard, true);
            document.removeEventListener('touchmove', this.scrollGuard, true);
            document.removeEventListener('touchstart', this.touchStartGuard, true);
        }

        this.unlockBodyScroll();
    },
    unlockBodyScroll() {
        if (this.bodyScrollState === null) {
            return;
        }

        const body = document.body;
        const root = document.documentElement;
        const { scrollY, rootOverflow, rootOverscrollBehavior, ...bodyStyles } = this.bodyScrollState;

        Object.entries(bodyStyles).forEach(([property, value]) => {
            body.style[property] = value;
        });

        root.style.overflow = rootOverflow;
        root.style.overscrollBehavior = rootOverscrollBehavior;
        this.bodyScrollState = null;
        const scrollBehavior = root.style.scrollBehavior;

        root.style.scrollBehavior = 'auto';
        window.scrollTo(0, scrollY);
        root.style.scrollBehavior = scrollBehavior;
    },
}" x-on:resize.window="
    if (mobileMenuOpen) mobileMenuOpen = false;
    if (accountSheetOpen) accountSheetOpen = false;
" x-on:keydown.escape.window="mobileMenuOpen = false; accountSheetOpen = false" x-init="$watch('mobileMenuOpen', (isOpen) => updateScrollGuards(isOpen))">
        <!-- Mobile topbar: compact brand and navigation controls -->
        <header class="topbar topbar-mobile">
            <button class="mobile-menu-toggle" type="button" aria-label="Buka menu navigasi" x-on:click="mobileMenuOpen = true">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-brand">
                <img class="brand-logo" src="{{ asset('images/logo-gjs.png') }}" alt="Gunawan Jaya Steel" />
                <span class="brand-name">Gunawan Jaya Steel</span>
            </div>
        </header>

        <!-- Desktop sidebar (shown on desktop) -->
        <aside class="sidebar" x-bind:class="{ 'open': mobileMenuOpen }">
            <div class="sidebar-brand-row">
                <a class="brand" href="{{ route('kasir') }}">
                    <img class="brand-logo" src="{{ asset('images/logo-gjs.png') }}" alt="Gunawan Jaya Steel" />
                    <strong class="brand-name">Gunawan Jaya Steel</strong>
                </a>
                <button class="sidebar-close" type="button" aria-label="Tutup menu navigasi" title="Tutup menu" x-on:click="mobileMenuOpen = false">&times;</button>
            </div>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <div class="nav-caption">MENU UTAMA</div>
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    Dashboard
                </a>
                <a class="nav-item {{ request()->routeIs('kasir', 'sales.create') ? 'active' : '' }}" href="{{ route('kasir') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    Kasir
                </a>
                <a class="nav-item {{ request()->routeIs('riwayat', 'sales.index') ? 'active' : '' }}" href="{{ route('riwayat') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                    Transaksi
                </a>
                <a class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.custom') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5"/><path d="M8 5.3 16 10"/></svg>
                    Pesanan
                </a>

                <div class="nav-caption">PENGELOLAAN</div>
                <a class="nav-item {{ request()->routeIs('products.*', 'categories.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 8 9 5 9-5M3 8v10l9 5 9-5V8m-9 5v10"/></svg>
                    Data Barang
                </a>
                <div class="sidebar-subnav" aria-label="Pengaturan data barang">
                    <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">Kategori</a>
                </div>
                <a class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 7h15m0 0-3-3m3 3-3 3M20 17H5m0 0 3 3m-3-3 3-3"/></svg>
                    Inventaris
                </a>
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 20V10m5 10V4m6 16v-7m5 7V7"/><path d="M2 20h20"/></svg>
                    Laporan
                </a>
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-account">
                    <a class="sidebar-account-toggle" href="{{ route('profile.edit') }}" aria-label="Buka profil {{ auth()->user()->name }}">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-chip-detail"><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></span>
                    </a>
                </div>
                <form class="sidebar-logout-form" method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Yakin ingin keluar?')">
                    @csrf
                    <button type="submit">Logout</button>
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
        <nav class="bottom-nav">
            <a class="bottom-nav-item {{ request()->routeIs('kasir', 'sales.create') ? 'active' : '' }}" href="{{ route('kasir') }}">
                <div class="nav-icon">▤</div>
                <span>Kasir</span>
            </a>
            <a class="bottom-nav-item {{ request()->routeIs('riwayat', 'sales.index') ? 'active' : '' }}" href="{{ route('riwayat') }}">
                <div class="nav-icon">⏱</div>
                <span>Riwayat</span>
            </a>
            <a class="bottom-nav-item bottom-nav-account" href="#" @click.prevent="accountSheetOpen = true">
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
                            <small>Administrator</small>
                        </div>
                    </div>
                    <a class="close-sheet" href="#" @click.prevent="accountSheetOpen = false">✕</a>
                </div>
                <div class="sheet-body">
                    <p class="muted">Pengaturan akun administrator.</p>
                    <a class="link-secondary" href="{{ route('profile.edit') }}">→ Akun Saya</a>
                    <a class="link-secondary" href="{{ route('profile.edit') }}#password">→ Ubah Password</a>
                    <form method="POST" action="{{ route('logout') }}" class="logout-row">
                        @csrf
                        <button type="submit" onclick="return confirm('Yakin ingin keluar?')">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        @livewireScripts
    </div>
</body>
</html>
