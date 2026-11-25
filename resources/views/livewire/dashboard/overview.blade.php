<div class="dashboard-overview">
    <header class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">Ringkasan toko</span>
            <h2>Selamat datang, {{ auth()->user()->name }}</h2>
            <p>Pantau aktivitas Gunawan Jaya Steel dan lanjutkan pekerjaan hari ini.</p>
        </div>
        <div class="dashboard-hero-actions">
            <span class="dashboard-date-badge">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M7.5 3v4M16.5 3v4M3.5 9.5h17"/></svg>
                {{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y') }}
            </span>
            <a class="button-primary dashboard-cashier-button" href="{{ route('kasir') }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 8h16v12H4zM7 8V5h10v3M8 12h2m4 0h2M8 16h8"/></svg>
                Buka kasir
            </a>
        </div>
    </header>

    <section class="dashboard-section" aria-labelledby="dashboard-today-heading">
        <div class="dashboard-section-heading">
            <div><h2 id="dashboard-today-heading">Ikhtisar toko</h2><p>Angka penting dari katalog dan aktivitas hari ini.</p></div>
        </div>
        <div class="metric-grid dashboard-metrics">
            <a class="metric-card metric-card-link dashboard-metric-card" href="{{ route('products.index') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg></span>Total produk</span>
                @if ($totalProducts > 0)
                    <strong>{{ number_format($totalProducts, 0, ',', '.') }}</strong>
                    <small>Barang aktif di katalog</small>
                @else
                    <strong class="metric-empty-state">Belum ada produk</strong>
                    <small>Tambahkan barang ke katalog</small>
                @endif
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card" href="{{ route('riwayat') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5v5h5M5 10a8 8 0 1 1-1 5M12 8v4l3 2"/></svg></span>Transaksi hari ini</span>
                @if ($todaySales > 0)
                    <strong>{{ number_format($todaySales, 0, ',', '.') }}</strong>
                    <small>Transaksi berhasil dibayar</small>
                @else
                    <strong class="metric-empty-state">Belum ada transaksi hari ini</strong>
                    <small>Transaksi tercatat setelah pembayaran</small>
                @endif
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card" href="{{ route('inventory.index') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/></svg></span>Total stok</span>
                @if ($totalStockByUnit->isNotEmpty())
                    <strong class="metric-stock-list">
                        @foreach ($totalStockByUnit as $stock)
                            <span wire:key="dashboard-stock-{{ $stock->abbreviation }}">@quantity($stock->total_quantity) {{ $stock->abbreviation }}</span>
                        @endforeach
                    </strong>
                    <small>Total stok dikelompokkan per satuan</small>
                @else
                    <strong class="metric-empty-state">Belum ada stok</strong>
                    <small>Stok barang akan tampil di sini</small>
                @endif
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card" href="{{ route('reports.index') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon dashboard-metric-icon-text" aria-hidden="true">Rp</span>Pendapatan hari ini</span>
                @if ($todaySales > 0)
                    <strong>@rupiah($todayRevenue)</strong>
                    <small>Dari transaksi yang berhasil dibayar</small>
                @else
                    <strong class="metric-empty-state">Belum ada transaksi hari ini</strong>
                    <small>Pendapatan tercatat setelah pembayaran</small>
                @endif
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <section class="dashboard-section dashboard-operations" aria-labelledby="dashboard-operations-heading">
        <div class="dashboard-section-heading">
            <div><h2 id="dashboard-operations-heading">Operasional</h2><p>Stok yang perlu dipantau, pengeluaran, dan pesanan.</p></div>
        </div>
        <div class="metric-grid dashboard-quick-stats">
            <a class="metric-card metric-card-link dashboard-metric-card dashboard-metric-compact" href="{{ route('inventory.index') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon dashboard-icon-warning" aria-hidden="true">!</span>Stok menipis</span>
                <strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong>
                <small>Butuh pemeriksaan</small>
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card dashboard-metric-compact" href="{{ route('inventory.index') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon dashboard-icon-muted" aria-hidden="true">0</span>Produk habis</span>
                <strong>{{ number_format($outOfStockCount, 0, ',', '.') }}</strong>
                <small>Saldo stok nol</small>
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card dashboard-metric-compact" href="{{ route('reports.index') }}#pengeluaran">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon dashboard-metric-icon-text" aria-hidden="true">−</span>Pengeluaran hari ini</span>
                <strong>@rupiah($todayExpenses)</strong>
                <small>Buka laporan pengeluaran</small>
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="metric-card metric-card-link dashboard-metric-card dashboard-metric-compact" href="{{ route('orders.custom') }}">
                <span class="dashboard-metric-label"><span class="dashboard-metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v15H4zM8 3v4m8-4v4M4 9h16M8 13h3m-3 4h8"/></svg></span>Pesanan terbaru</span>
                <strong>{{ number_format($recentOrders->count(), 0, ',', '.') }}</strong>
                <small>Buka daftar pesanan</small>
                <span class="dashboard-card-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <div class="dashboard-lists">
        <section class="catalog-panel dashboard-panel">
            <div class="catalog-panel-heading"><div><h2>Penjualan terbaru</h2><p>Catatan transaksi terbaru.</p></div><a class="text-link" href="{{ route('riwayat') }}">Lihat riwayat <span aria-hidden="true">→</span></a></div>
            <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Waktu</th></tr></thead><tbody>
                @forelse($recentSales as $sale)
                    <tr wire:key="dash-sale-{{ $sale->id }}"><td>{{ $sale->invoice_number }}</td><td>{{ $sale->customer_name ?: '—' }}</td><td>@rupiah($sale->total)</td><td>{{ $sale->created_at->timezone('Asia/Jakarta')->format('H:i · d/m') }}</td></tr>
                @empty
                    <tr><td colspan="4">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="catalog-panel dashboard-panel">
            <div class="catalog-panel-heading"><div><h2>Stok minimum</h2><p>Barang yang perlu diperiksa.</p></div><a class="text-link" href="{{ route('inventory.index') }}">Inventaris <span aria-hidden="true">→</span></a></div>
            <div class="low-stock-list">
                @forelse($lowStock as $level)
                    <div class="low-stock-row" wire:key="low-stock-{{ $level->id }}"><span><strong>{{ $level->product->name }}</strong><small>{{ $level->product->sku }}</small></span><b>@quantity($level->quantity) / @quantity($level->product->minimum_stock) {{ $level->product->pricing_method === 'weight' ? 'kg' : $level->product->unit->abbreviation }}</b></div>
                @empty
                    <p class="empty-state">Tidak ada stok minimum.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="catalog-panel dashboard-panel dashboard-orders">
        <div class="catalog-panel-heading"><div><h2>Aktivitas inventaris terbaru</h2><p>Perubahan stok dari pembelian, penyesuaian, dan transaksi.</p></div><a class="text-link" href="{{ route('inventory.index') }}">Lihat inventaris <span aria-hidden="true">→</span></a></div>
        <div class="dashboard-order-list">
            @forelse($recentMovements as $movement)
                <div class="dashboard-order-row" wire:key="dashboard-movement-{{ $movement->id }}"><span><strong>{{ $movement->product->name }}</strong><small>{{ $movement->user->name }} · {{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} · {{ $movement->notes ?: ($movement->reference ?: '—') }}</small></span><span class="catalog-status {{ $movement->quantity < 0 ? 'catalog-status-off' : 'catalog-status-on' }}">{{ $movement->quantity > 0 ? '+' : '' }}@quantity($movement->quantity) {{ $movement->product->pricing_method === 'weight' ? 'kg' : $movement->product->unit->abbreviation }}</span></div>
            @empty
                <p class="empty-state">Belum ada aktivitas inventaris.</p>
            @endforelse
        </div>
    </section>

    <section class="catalog-panel dashboard-panel dashboard-orders">
        <div class="catalog-panel-heading"><div><h2>Pesanan terbaru</h2><p>Status proses dan jadwal pengambilan.</p></div><a class="text-link" href="{{ route('orders.custom') }}">Kelola pesanan <span aria-hidden="true">→</span></a></div>
        <div class="dashboard-order-list">
            @forelse($recentOrders as $order)
                <div class="dashboard-order-row"><span><strong>{{ $order->customer_name }}</strong><small>{{ $order->order_number }} · {{ $order->estimated_completion_date?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</small></span><span class="catalog-status">{{ ['pending'=>'Menunggu','producing'=>'Diproduksi','made'=>'Selesai dibuat','ready'=>'Siap diambil','delivered'=>'Selesai','canceled'=>'Dibatalkan'][$order->status] }}</span></div>
            @empty
                <p class="empty-state">Belum ada pesanan.</p>
            @endforelse
        </div>
    </section>
</div>
