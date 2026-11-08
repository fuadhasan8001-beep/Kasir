<div class="inventory-layout">
    <section class="catalog-panel"><div class="catalog-panel-heading"><div><h2>Catat pengeluaran</h2><p>Biaya dicatat oleh pemilik untuk laporan toko.</p></div></div>
        @if(session('status'))<div class="notice-card"><div><strong>{{ session('status') }}</strong></div></div>@endif
        <form class="catalog-form" wire:submit="save">
            <label>Kategori<input list="expense-categories" type="text" wire:model="category" placeholder="Mis. listrik, sewa, transportasi"><datalist id="expense-categories">@foreach($categories as $categoryOption)<option value="{{ $categoryOption }}">@endforeach</datalist>@error('category')<small>{{ $message }}</small>@enderror</label>
            <label>Keterangan<input type="text" wire:model="description" placeholder="Keterangan biaya">@error('description')<small>{{ $message }}</small>@enderror</label>
            <div class="pos-input-grid"><label>Jumlah (Rp)<input type="number" min="1" step="100" wire:model="amount" placeholder="0">@error('amount')<small>{{ $message }}</small>@enderror</label><label>Tanggal<input type="date" wire:model="expenseDate">@error('expenseDate')<small>{{ $message }}</small>@enderror</label></div>
            <button class="button-primary" type="submit" wire:loading.attr="disabled">Simpan pengeluaran</button>
        </form>
    </section>
    <section class="catalog-panel"><div class="catalog-panel-heading"><div><h2>Riwayat pengeluaran</h2><p>Data pengeluaran tersimpan permanen.</p></div><span class="period-pill">Bulan ini · Rp {{ number_format($totalThisMonth, 0, ',', '.') }}</span></div><div class="table-scroll"><table class="catalog-table"><thead><tr><th>Tanggal</th><th>Kategori / keterangan</th><th>Dicatat oleh</th><th>Jumlah</th><th></th></tr></thead><tbody>
        @forelse($expenses as $expense)<tr wire:key="expense-{{ $expense->id }}"><td>{{ $expense->expense_date->format('d/m/Y') }}</td><td><strong>{{ $expense->category }}</strong><small class="table-subtext">{{ $expense->description }}</small></td><td>{{ $expense->user->name }}</td><td>Rp {{ number_format($expense->amount, 0, ',', '.') }}</td><td><button class="button-danger button-small" type="button" wire:confirm="Hapus catatan pengeluaran ini?" wire:click="delete({{ $expense->id }})">Hapus</button></td></tr>@empty<tr><td colspan="5">Belum ada pengeluaran tercatat.</td></tr>@endforelse
    </tbody></table></div>{{ $expenses->links() }}</section>
</div>
