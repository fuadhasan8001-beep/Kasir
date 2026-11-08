<div class="catalog-panel transaction-history">
    <div class="filter-row">
        <label>Pencarian invoice<input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Nomor invoice"></label>
        <label>Dari tanggal<input class="catalog-input" type="date" wire:model.live="dateFrom"></label>
        <label>Sampai tanggal<input class="catalog-input" type="date" wire:model.live="dateTo"></label>
    </div>
    <div class="table-scroll"><table class="catalog-table"><thead><tr><th>Invoice / waktu</th><th>Kasir</th><th>Produk</th><th>Pembayaran</th><th>Total</th>@if($canViewProfit)<th>Laba kotor</th>@endif<th>Status</th><th></th></tr></thead><tbody>
        @forelse($sales as $sale)
            <tr wire:key="sale-{{ $sale->id }}"><td><strong>{{ $sale->invoice_number }}</strong><small class="table-subtext">{{ $sale->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</small></td><td>{{ $sale->user->name }}</td><td>{{ $sale->items->count() }} jenis barang</td><td>{{ ['cash' => 'Tunai', 'bank_transfer' => 'Transfer bank', 'qris' => 'QRIS'][$sale->payment_method] ?? $sale->payment_method }}<small class="table-subtext">Diterima Rp {{ number_format($sale->amount_received, 0, ',', '.') }}</small></td><td>Rp {{ number_format($sale->total, 0, ',', '.') }}</td>@if($canViewProfit)<td>@if($sale->items->every(fn ($item) => (float) $item->unit_cost > 0) && $sale->items->isNotEmpty())Rp {{ number_format($sale->items->sum(fn ($item) => (float) $item->line_total - (((float) ($item->actual_weight ?? $item->quantity)) * (float) $item->unit_cost)) - (float) $sale->discount, 0, ',', '.') }}@else Data modal belum lengkap @endif</td>@endif<td><span class="catalog-status catalog-status-on">{{ $sale->payment_status === 'paid' ? 'Lunas' : $sale->payment_status }}</span></td><td><button class="button-secondary button-small" type="button" wire:click="toggleDetails({{ $sale->id }})">{{ $expandedSaleId === $sale->id ? 'Tutup' : 'Detail' }}</button></td></tr>
            @if($expandedSaleId === $sale->id)
                <tr wire:key="sale-detail-{{ $sale->id }}"><td colspan="{{ $canViewProfit ? 8 : 7 }}"><div class="sale-detail"><div class="sale-detail-heading"><strong>Rincian {{ $sale->invoice_number }}</strong><span>Diskon Rp {{ number_format($sale->discount, 0, ',', '.') }} · Kembalian Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span></div>@foreach($sale->items as $item)<div class="sale-line"><div><strong>{{ $item->product_name }}</strong><small>{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->unit_name }}@if($item->actual_weight) · berat {{ number_format($item->actual_weight, 3, ',', '.') }} kg @endif</small>@if($item->specifications)<small>{{ collect($item->specifications)->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>@endif@if($item->notes)<small>{{ $item->notes }}</small>@endif</div><span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span></div>@endforeach</div></td></tr>
            @endif
        @empty <tr><td colspan="8">Tidak ada transaksi pada rentang ini.</td></tr> @endforelse
    </tbody></table></div>
    {{ $sales->links() }}
</div>
