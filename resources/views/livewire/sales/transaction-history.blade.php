<div class="catalog-panel transaction-history">
    <div class="filter-row">
        <label>Tahun<input class="catalog-input" type="number" min="2000" max="2100" wire:model.live="year" placeholder="{{ now()->year }}"></label>
        <label>Bulan<select class="catalog-input" wire:model.live="month"><option value="">Semua bulan</option>@foreach(range(1, 12) as $monthNumber)<option value="{{ $monthNumber }}">{{ now()->setMonth($monthNumber)->translatedFormat('F') }}</option>@endforeach</select></label>
    </div>
    <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Catatan / waktu</th><th>Admin</th><th>Produk</th><th>Nilai catatan</th><th></th></tr></thead><tbody>
        @forelse($sales as $sale)
            <tr wire:key="sale-{{ $sale->id }}"><td><strong>{{ $sale->invoice_number }}</strong><small class="table-subtext">{{ $sale->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</small></td><td>{{ $sale->user->name }}</td><td>{{ $sale->items->count() }} jenis barang</td><td>Rp {{ number_format($sale->total, 0, ',', '.') }}</td><td><button class="button-secondary button-small" type="button" wire:click="toggleDetails({{ $sale->id }})">{{ $expandedSaleId === $sale->id ? 'Tutup' : 'Detail' }}</button></td></tr>
            @if($expandedSaleId === $sale->id)
                <tr wire:key="sale-detail-{{ $sale->id }}"><td colspan="5"><div class="sale-detail"><div class="sale-detail-heading"><strong>Rincian {{ $sale->invoice_number }}</strong><span>Jumlah nilai catatan Rp {{ number_format($sale->total, 0, ',', '.') }}</span></div>@foreach($sale->items as $item)<div class="sale-line"><div><strong>{{ $item->product_name }}</strong><small>{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->unit_name }}@if($item->actual_weight) · berat {{ number_format($item->actual_weight, 3, ',', '.') }} kg @endif</small>@if($item->specifications)<small>{{ collect($item->specifications)->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>@endif@if($item->notes)<small>{{ $item->notes }}</small>@endif</div><span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span></div>@endforeach</div></td></tr>
            @endif
        @empty <tr><td colspan="8">Tidak ada transaksi pada rentang ini.</td></tr> @endforelse
    </tbody></table></div>
    {{ $sales->links() }}
</div>
