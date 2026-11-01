<div class="space-y-6">
    @if (session('status'))<div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>@endif
    <div class="grid gap-6 xl:grid-cols-[minmax(300px,0.7fr)_minmax(0,1.3fr)]">
        <section class="catalog-panel h-fit">
            <div class="catalog-panel-heading"><div><h2>{{ $editingUnitId ? 'Ubah satuan' : 'Tambah satuan' }}</h2><p>Atur presisi kuantitas sesuai karakter barang.</p></div></div>
            <form wire:submit="save" class="catalog-form">
                <label>Nama satuan<input wire:model="name" type="text" placeholder="Contoh: Kilogram">@error('name')<small>{{ $message }}</small>@enderror</label>
                <label>Singkatan<input wire:model="abbreviation" type="text" placeholder="kg">@error('abbreviation')<small>{{ $message }}</small>@enderror</label>
                <label>Jumlah angka desimal<input wire:model="decimalPlaces" type="number" min="0" max="6">@error('decimalPlaces')<small>{{ $message }}</small>@enderror</label>
                <div class="flex gap-2"><button class="button-primary" type="submit">{{ $editingUnitId ? 'Simpan perubahan' : 'Simpan satuan' }}</button>@if($editingUnitId)<button class="button-secondary" wire:click="cancelEdit" type="button">Batal</button>@endif</div>
            </form>
        </section>
        <section class="catalog-panel">
            <div class="catalog-panel-heading"><div><h2>Daftar satuan</h2><p>Satuan yang dipakai produk tidak dapat dihapus.</p></div></div>
            <input class="catalog-input mb-4" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau singkatan...">
            <div class="overflow-x-auto"><table class="catalog-table"><thead><tr><th>Satuan</th><th>Presisi</th><th>Produk</th><th class="text-right">Tindakan</th></tr></thead><tbody>
                @forelse ($units as $unit)
                    <tr wire:key="unit-{{ $unit->id }}"><td><div class="font-medium text-neutral-900">{{ $unit->name }}</div><div class="text-xs text-neutral-500">{{ $unit->abbreviation }}</div></td><td>{{ $unit->decimal_places }} digit</td><td>{{ $unit->products_count }}</td><td><div class="flex justify-end gap-2"><button class="button-secondary button-small" wire:click="edit({{ $unit->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="delete({{ $unit->id }})" wire:confirm="Hapus satuan {{ $unit->name }}?" type="button">Hapus</button></div></td></tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-neutral-500">Belum ada satuan.</td></tr>
                @endforelse
            </tbody></table></div><div class="mt-4">{{ $units->links() }}</div>
        </section>
    </div>
</div>
