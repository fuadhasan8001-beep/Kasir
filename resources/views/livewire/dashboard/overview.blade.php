<div class="dashboard-overview">
    <div class="welcome-row">
        <div>
            <h2>Selamat datang, {{ auth()->user()->name }}</h2>
            <p>Ringkasan aktivitas Gunawan Jaya Steel · {{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
        </div>
        <a class="button-primary" href="{{ route('kasir') }}">Buka kasir</a>
    </div>

    <div class="metric-grid dashboard-metrics">
        <a class="metric-card metric-card-link" href="{{ route('products.index') }}">
            <span>Total Produk</span>
            @if ($totalProducts > 0)
                <strong>{{ number_format($totalProducts, 0, ',', '.') }}</strong>
                <small>Barang aktif di katalog</small>
            @else
                <strong class="metric-empty-state">Belum ada produk</strong>
                <small>Tambahkan barang ke katalog</small>
            @endif
        </a>
        <a class="metric-card metric-card-link" href="{{ route('riwayat') }}">
            <span>Transaksi Hari Ini</span>
            @if ($todaySales > 0)
                <strong>{{ number_format($todaySales, 0, ',', '.') }}</strong>
                <small>Transaksi berhasil dibayar</small>
            @else
                <strong class="metric-empty-state">Belum ada transaksi hari ini</strong>
                <small>Transaksi tercatat setelah pembayaran</small>
            @endif
        </a>
        <a class="metric-card metric-card-link" href="{{ route('inventory.index') }}">
            <span>Total Stok</span>
            @if ($totalStockByUnit->isNotEmpty())
                <strong class="metric-stock-list">
                    @foreach ($totalStockByUnit as $stock)
                        <span wire:key="dashboard-stock-{{ $stock->abbreviation }}">
                            @quantity($stock->total_quantity) {{ $stock->abbreviation }}
                        </span>
                    @endforeach
                </strong>
                <small>Total stok dikelompokkan per satuan</small>
            @else
                <strong class="metric-empty-state">Belum ada stok</strong>
                <small>Stok barang akan tampil di sini</small>
            @endif
        </a>
        <a class="metric-card metric-card-link" href="{{ route('reports.index') }}">
            <span>Pendapatan Hari Ini</span>
            @if ($todaySales > 0)
                <strong>@rupiah($todayRevenue)</strong>
                <small>Dari transaksi yang berhasil dibayar</small>
            @else
                <strong class="metric-empty-state">Belum ada transaksi hari ini</strong>
                <small>Pendapatan tercatat setelah pembayaran</small>
            @endif
        </a>
        <a class="metric-card metric-card-link" href="{{ route('inventory.index') }}"><span>Stok menipis</span><strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong><small>Butuh pemeriksaan</small></a>
        <a class="metric-card metric-card-link" href="{{ route('inventory.index') }}"><span>Produk habis</span><strong>{{ number_format($outOfStockCount, 0, ',', '.') }}</strong><small>Saldo stok nol</small></a>
        <a class="metric-card metric-card-link" href="{{ route('reports.index') }}#pengeluaran"><span>Pengeluaran hari ini</span><strong>@rupiah($todayExpenses)</strong><small>Buka bagian laporan pengeluaran</small></a>
        <a class="metric-card metric-card-link" href="{{ route('orders.custom') }}"><span>Pesanan terbaru</span><strong>{{ number_format($recentOrders->count(), 0, ',', '.') }}</strong><small>Buka daftar pesanan</small></a>
    </div>
    <div class="dashboard-lists">
        <section class="catalog-panel"><div class="catalog-panel-heading"><div><h2>Penjualan terbaru</h2><p>Catatan transaksi terbaru.</p></div><a class="text-link" href="{{ route('riwayat') }}">Lihat riwayat →</a></div><div class="table-scroll"><table class="catalog-table"><thead><tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Waktu</th></tr></thead><tbody>@forelse($recentSales as $sale)<tr wire:key="dash-sale-{{ $sale->id }}"><td>{{ $sale->invoice_number }}</td><td>{{ $sale->customer_name ?: '—' }}</td><td>@rupiah($sale->total)</td><td>{{ $sale->created_at->timezone('Asia/Jakarta')->format('H:i · d/m') }}</td></tr>@empty<tr><td colspan="4">Belum ada transaksi.</td></tr>@endforelse</tbody></table></div></section>
        <section class="catalog-panel"><div class="catalog-panel-heading"><div><h2>Stok minimum</h2><p>Barang yang perlu diperiksa.</p></div><a class="text-link" href="{{ route('inventory.index') }}">Inventaris →</a></div><div class="low-stock-list">@forelse($lowStock as $level)<div class="low-stock-row" wire:key="low-stock-{{ $level->id }}"><span><strong>{{ $level->product->name }}</strong><small>{{ $level->product->sku }}</small></span><b>@quantity($level->quantity) / @quantity($level->product->minimum_stock) {{ $level->product->pricing_method === 'weight' ? 'kg' : $level->product->unit->abbreviation }}</b></div>@empty<p class="empty-state">Tidak ada stok minimum.</p>@endforelse</div></section>
    </div>
    <section class="catalog-panel dashboard-orders"><div class="catalog-panel-heading"><div><h2>Aktivitas inventaris terbaru</h2><p>Perubahan stok dari pembelian, penyesuaian, dan transaksi.</p></div><a class="text-link" href="{{ route('inventory.index') }}">Lihat inventaris →</a></div><div class="dashboard-order-list">@forelse($recentMovements as $movement)<div class="dashboard-order-row" wire:key="dashboard-movement-{{ $movement->id }}"><span><strong>{{ $movement->product->name }}</strong><small>{{ $movement->user->name }} · {{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} · {{ $movement->notes ?: ($movement->reference ?: '—') }}</small></span><span class="catalog-status {{ $movement->quantity < 0 ? 'catalog-status-off' : 'catalog-status-on' }}">{{ $movement->quantity > 0 ? '+' : '' }}@quantity($movement->quantity) {{ $movement->product->pricing_method === 'weight' ? 'kg' : $movement->product->unit->abbreviation }}</span></div>@empty<p class="empty-state">Belum ada aktivitas inventaris.</p>@endforelse</div></section>
    <section class="catalog-panel dashboard-orders"><div class="catalog-panel-heading"><div><h2>Pesanan terbaru</h2><p>Status proses dan jadwal pengambilan.</p></div><a class="text-link" href="{{ route('orders.custom') }}">Kelola pesanan →</a></div><div class="dashboard-order-list">@forelse($recentOrders as $order)<div class="dashboard-order-row"><span><strong>{{ $order->customer_name }}</strong><small>{{ $order->order_number }} · {{ $order->estimated_completion_date?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</small></span><span class="catalog-status">{{ ['pending'=>'Menunggu','producing'=>'Diproduksi','made'=>'Selesai dibuat','ready'=>'Siap diambil','delivered'=>'Selesai','canceled'=>'Dibatalkan'][$order->status] }}</span></div>@empty<p class="empty-state">Belum ada pesanan.</p>@endforelse</div></section>
</div>
