<div class="catalog-panel transaction-history records-panel">
    <div class="history-summary"><article><span>Transaksi</span><strong>{{ number_format($transactionCount, 0, ",", ".") }}</strong></article><article><span>Total nilai</span><strong>@rupiah($totalValue)</strong></article></div>
    @if($activeCartItems !== [])
        <section class="mb-5 rounded-lg border border-neutral-200 bg-neutral-50 p-4" aria-labelledby="active-purchase-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="active-purchase-title" class="text-sm font-semibold text-neutral-900">Pembelian berjalan</h2>
                    <p class="mt-1 text-xs text-neutral-500">Barang dari Kasir tersimpan di sini sampai pembayaran diselesaikan.</p>
                </div>
                <a class="button-primary button-small" href="{{ route('kasir.cart') }}">Lanjutkan pembelian</a>
            </div>
            <div class="mt-3 divide-y divide-neutral-200">
                @foreach($activeCartItems as $activeCartItem)
                    <div class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="active-purchase-{{ $activeCartItem['key'] }}">
                        <div>
                            <strong class="text-sm text-neutral-800">{{ $activeCartItem['product_name'] }}</strong>
                            <div class="text-xs text-neutral-500">
                                @if($activeCartItem['pricing_basis'] === 'weight')
                                    @quantity($activeCartItem['actual_weight']) kg
                                @else
                                    @quantity($activeCartItem['quantity']) {{ $activeCartItem['unit_name'] }}
                                @endif
                                @if($activeCartItem['specifications']) · {{ collect($activeCartItem['specifications'])->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}@endif
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-neutral-800">@rupiah($activeCartItem['line_total'])</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex justify-between border-t border-neutral-200 pt-3 text-sm font-semibold"><span>Total pembelian berjalan</span><span>@rupiah($activeCartTotal)</span></div>
        </section>
    @endif
    <div class="filter-row">
        <label class="history-search">Cari invoice<input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="INV-…"></label>
        <label>Nama pembeli<input class="catalog-input" type="search" wire:model.live.debounce.300ms="customerName" placeholder="Nama pembeli"></label>
        <label>Dari tanggal<input class="catalog-input" type="date" wire:model.live="dateFrom"></label>
        <label>Sampai tanggal<input class="catalog-input" type="date" wire:model.live="dateTo"></label>
        <label>Status<select class="catalog-input" wire:model.live="status"><option value="">Semua</option><option value="paid">Lunas</option><option value="unpaid">Belum lunas</option></select></label>
        <label>Tahun<input class="catalog-input" type="number" min="2000" max="2100" wire:model.live="year" placeholder="{{ now()->year }}"></label>
        <label>Bulan<select class="catalog-input" wire:model.live="month"><option value="">Semua bulan</option>@foreach(range(1, 12) as $monthNumber)<option value="{{ $monthNumber }}">{{ now()->setMonth($monthNumber)->translatedFormat('F') }}</option>@endforeach</select></label>
    </div>
    <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Invoice / waktu</th><th>Pelanggan</th><th>Admin</th><th>Produk</th><th>Total / status</th><th></th></tr></thead><tbody>
        @forelse($sales as $sale)
            <tr wire:key="sale-{{ $sale->id }}"><td><strong>{{ $sale->invoice_number }}</strong><small class="table-subtext">{{ $sale->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</small></td><td>{{ $sale->customer_name ?: '—' }}</td><td>{{ $sale->user->name }}</td><td>{{ $sale->items->count() }} jenis barang</td><td>@rupiah($sale->total)<small class="table-subtext">{{ $sale->payment_status === 'paid' ? 'Lunas · tunai' : 'Belum lunas' }}</small></td><td><button class="button-secondary button-small" type="button" wire:click="toggleDetails({{ $sale->id }})">{{ $expandedSaleId === $sale->id ? 'Tutup' : 'Detail' }}</button></td></tr>
            @if($expandedSaleId === $sale->id)
                <tr wire:key="sale-detail-{{ $sale->id }}"><td colspan="6"><div class="sale-detail"><div class="sale-detail-heading"><strong>Rincian {{ $sale->invoice_number }}</strong><span>Jumlah nilai catatan @rupiah($sale->total)</span><button class="button-secondary button-small no-print" type="button" onclick="window.print()">Cetak ulang struk</button></div>@foreach($sale->items as $item)<div class="sale-line"><div><strong>{{ $item->product_name }}</strong><small>@quantity($item->quantity) {{ $item->unit_name }}@if($item->actual_weight) · berat @quantity($item->actual_weight) kg @endif</small>@if($item->specifications)<small>{{ collect($item->specifications)->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>@endif@if($item->notes)<small>{{ $item->notes }}</small>@endif</div><span>@rupiah($item->line_total)</span></div>@endforeach<div class="sale-line"><span>Berat total</span><strong>@quantity((float) $sale->items->sum('actual_weight')) kg</strong></div><div class="sale-line"><span>Pembayaran</span><strong>{{ $sale->payment_method === 'cash' ? 'Tunai' : '—' }} · {{ $sale->payment_status === 'paid' ? 'Selesai' : 'Belum selesai' }}</strong></div><div class="print-receipt"><h2>Gunawan Jaya Steel</h2><p>{{ $sale->invoice_number }} · {{ $sale->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</p>@foreach($sale->items as $item)<div><span>{{ $item->product_name }} · @quantity($item->quantity) {{ $item->unit_name }}</span><b>@rupiah($item->line_total)</b></div>@endforeach<hr><div><strong>Total</strong><strong>@rupiah($sale->total)</strong></div><div><span>Tunai diterima</span><b>@rupiah($sale->amount_received)</b></div><div><span>Kembalian</span><b>@rupiah($sale->change_amount)</b></div></div></div></td></tr>
            @endif
        @empty <tr><td colspan="8">Tidak ada transaksi pada rentang ini.</td></tr> @endforelse
    </tbody></table></div>
    {{ $sales->links() }}
</div>
