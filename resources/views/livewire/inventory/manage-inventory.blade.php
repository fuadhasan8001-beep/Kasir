<div class="inventory-page">
    @if (session('status')) <div class="notice-card"><div><strong>{{ session('status') }}</strong></div></div> @endif
    <section class="inventory-summary" aria-label="Ringkasan stok">
        <article class="metric-card"><span>Jenis barang</span><strong>{{ $stockSummary->product_count ?? 0 }}</strong><small>Produk aktif terdaftar</small></article>
        <article class="metric-card"><span>Tersedia</span><strong>{{ $stockSummary->available_stock_count ?? 0 }}</strong><small>Di atas stok minimum</small></article>
        <article class="metric-card"><span>Stok menipis</span><strong>{{ $stockSummary->low_stock_count ?? 0 }}</strong><small>Lebih dari 0 hingga minimum</small></article>
        <article class="metric-card"><span>Stok habis</span><strong>{{ $stockSummary->out_of_stock_count ?? 0 }}</strong><small>Saldo stok 0</small></article>
    </section>
    <div class="inventory-layout">
        <section id="catat-stok" class="catalog-panel inventory-entry-panel">
            <div class="catalog-panel-heading"><div><h2>Catat perubahan stok</h2><p>Setiap perubahan dicatat dalam riwayat inventaris.</p></div></div>
            <form class="catalog-form" wire:submit="saveMovement">
                <label>Barang<select wire:model="productId"><option value="">Pilih barang</option>@foreach ($movementProducts as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }})</option>@endforeach</select>@error('productId')<small>{{ $message }}</small>@enderror</label>
                <label>Jenis perubahan<select wire:model="movementType"><option value="in">Barang jadi masuk / produksi selesai</option><option value="out">Barang jadi keluar</option><option value="adjustment">Penyesuaian (selisih stok)</option></select></label>
                <label>{{ $movementType === 'adjustment' ? 'Selisih stok (+/-)' : 'Jumlah' }}<input type="number" step="0.001" wire:model="quantity" placeholder="Masukkan jumlah"><small>Stok di sini adalah barang jadi yang sudah selesai dan siap dijual.</small>@error('quantity')<small>{{ $message }}</small>@enderror</label>
                <label>Alasan / keterangan perubahan<textarea rows="2" wire:model="notes" placeholder="Contoh: hasil produksi selesai, stok opname, atau alasan barang keluar"></textarea>@error('notes')<small>{{ $message }}</small>@enderror</label>
                <button class="button-primary" type="submit" wire:loading.attr="disabled">Simpan perubahan stok</button>
            </form>
        </section>
        <section class="catalog-panel inventory-stock-panel">
            <div class="catalog-panel-heading"><div><h2>Persediaan barang jadi</h2><p>Stok saat ini dibandingkan batas minimum setiap barang.</p></div></div>
            <input class="catalog-input inventory-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari barang atau SKU">
            <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Barang</th><th>Stok</th><th>Minimum</th><th>Status</th></tr></thead><tbody>
                @forelse ($products as $product)
                    @php($stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0))
                    @php($stockStatus = $stock <= 0 ? 'Habis' : ($stock <= (float) $product->minimum_stock ? 'Stok menipis' : 'Tersedia'))
                    <tr wire:key="inventory-{{ $product->id }}"><td><strong>{{ $product->name }}</strong><small class="table-subtext">{{ $product->sku }}</small></td><td>@quantity($stock) {{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }}</td><td>@quantity($product->minimum_stock) {{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }}</td><td><span class="catalog-status {{ $stock <= (float) $product->minimum_stock ? 'catalog-status-off' : 'catalog-status-on' }}">{{ $stockStatus }}</span></td></tr>
                @empty <tr><td colspan="4">Belum ada barang aktif di katalog.</td></tr> @endforelse
            </tbody></table></div>
            {{ $products->links() }}
        </section>
    </div>
    <section class="catalog-panel movement-history inventory-movement-panel"><div class="catalog-panel-heading"><div><h2>Riwayat pergerakan stok</h2><p>Perubahan stok terbaru beserta pengguna yang mencatat.</p></div></div>
        <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Waktu</th><th>Barang</th><th>Jenis</th><th>Stok sebelum</th><th>Perubahan</th><th>Stok sesudah</th><th>Pengguna</th><th>Alasan</th></tr></thead><tbody>
            @forelse ($movements as $movement)@php($movementUnit = $movement->product->pricing_method === 'weight' ? 'kg' : $movement->product->unit->abbreviation)<tr wire:key="movement-{{ $movement->id }}"><td>{{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td>{{ $movement->product->name }}</td><td>{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian', 'sale' => 'Penjualan', 'custom_order_ready' => 'Pesanan custom siap', 'custom_order_delivery' => 'Pesanan custom diserahkan'][$movement->movement_type] ?? $movement->movement_type }}<small class="table-subtext">{{ $movement->inventory_type === 'material' ? 'Bahan baku' : 'Barang jadi' }}</small></td><td>@quantity((float) $movement->balance_after - (float) $movement->quantity) {{ $movementUnit }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}@quantity($movement->quantity) {{ $movementUnit }}</td><td>@quantity($movement->balance_after) {{ $movementUnit }}</td><td>{{ $movement->user->name }}</td><td>{{ $movement->reference ?: $movement->notes ?: '—' }}</td></tr>
            @empty <tr><td colspan="7">Belum ada perubahan stok tercatat.</td></tr> @endforelse
        </tbody></table></div>{{ $movements->links() }}
    </section>
</div>
