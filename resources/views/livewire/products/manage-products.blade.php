<div class="products-livewire">
    @if (session('status'))<div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>@endif
    <div class="catalog-panel product-directory-panel">
        <div class="product-directory-heading">
            <div><h2>Katalog barang</h2><p>Data barang, satuan, harga, dan spesifikasi per kategori.</p></div>
            <button class="button-primary product-add-button" wire:click="create" type="button"><span aria-hidden="true">+</span> Barang baru</button>
        </div>
        <div class="product-directory-filters">
            <input class="catalog-input" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau kode barang...">
            <select class="catalog-input" wire:model.live="categoryFilter"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        </div>
        <div class="product-table-wrap"><table class="catalog-table"><thead><tr><th>Barang</th><th>Kategori</th><th>Harga</th><th>Satuan</th><th>Stok</th><th>Status</th><th class="text-right">Tindakan</th></tr></thead><tbody>
            @forelse ($products as $product)
                <tr wire:key="product-{{ $product->id }}">
                    <td><div class="flex items-start gap-3"><img class="product-thumb" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'"><div><div class="font-medium text-neutral-900">{{ $product->name }}</div><div class="mt-1 text-xs text-neutral-500">{{ $product->sku }}</div>@if($product->brand)<div class="mt-1 text-xs text-neutral-500">{{ $product->brand->name }}</div>@endif
                    </div></div></td>
                    <td>{{ $product->category->name }}</td>
                    @php($diameterPrices = collect($product->category->dimension_sets ?? [])->filter(fn (array $set): bool => filled($set['diameter'] ?? null)))
                    <td>
                        @if($diameterPrices->isNotEmpty())
                            <div class="space-y-1">
                                @foreach($diameterPrices as $diameterPrice)
                                    <div>{{ $diameterPrice['diameter'] }} · @rupiah($diameterPrice['price'] ?? 0)/kg</div>
                                @endforeach
                            </div>
                        @elseif($product->category->dimension_sets)
                            Mengikuti harga ukuran
                        @else
                            Atur di Atribut Kategori
                        @endif
                    </td>
                    <td>{{ $product->unit->name }} <span class="text-xs text-neutral-500">({{ $product->unit->abbreviation }})</span></td>
                    @php($stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0))
                    <td>@quantity($stock) {{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }}</td>
                    <td><span class="catalog-status {{ ! $product->is_active || $stock <= 0 || $stock <= (float) $product->minimum_stock ? 'catalog-status-off' : 'catalog-status-on' }}">{{ ! $product->is_active ? 'Nonaktif' : ($stock <= 0 ? 'Habis' : ($stock <= (float) $product->minimum_stock ? 'Stok menipis' : 'Tersedia')) }}</span></td>
                    <td><div class="flex justify-end gap-2"><button class="button-secondary button-small" wire:click="viewDetails({{ $product->id }})" type="button">Detail</button><button class="button-secondary button-small" wire:click="edit({{ $product->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="delete({{ $product->id }})" wire:confirm="Hapus barang {{ $product->name }} secara permanen?" type="button">Hapus</button></div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-neutral-500">Tidak ada barang yang cocok dengan pencarian.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>

    @if ($showForm)
        <div class="catalog-modal-backdrop">
            <section class="catalog-modal" role="dialog" aria-modal="true" aria-labelledby="product-form-title">
                <div class="catalog-panel-heading"><div><h2 id="product-form-title">{{ $editingProductId ? 'Ubah barang' : 'Tambah barang' }}</h2><p>Stok mengikuti satuan jual produk; setiap penyesuaian akan masuk ke riwayat inventaris.</p></div><button class="button-secondary button-small" wire:click="cancel" type="button">Tutup</button></div>
                <form wire:submit="save" class="catalog-form product-form-single-column mt-5">
                    <div class="grid grid-cols-1 gap-4">
                        <label>Kode barang (otomatis)<input wire:model="sku" type="text" readonly><small>Kode dibuat otomatis dan tidak perlu diisi.</small></label>
                        <label>Nama barang<input wire:model="name" type="text" maxlength="180" placeholder="Nama produk">@error('name')<small>{{ $message }}</small>@enderror</label>
                        <label>Merek<input wire:model="brandName" type="text" maxlength="120" placeholder="Merek (opsional)">@error('brandName')<small>{{ $message }}</small>@enderror</label>
                        <label>Kategori<select wire:model.live="categoryId"><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('categoryId')<small>{{ $message }}</small>@enderror</label>
                        <label>Metode harga<div class="catalog-input flex min-h-11 items-center bg-neutral-100 text-neutral-700">Per kilogram</div><input type="hidden" wire:model="pricingMethod" value="weight"></label>
                        <label>{{ $editingProductId ? 'Stok saat ini' : 'Stok awal (barang masuk)' }} (kg)<input wire:model="stockQuantity" type="number" min="0" step="0.001">@if(!$editingProductId)<small>Jumlah barang yang tersedia saat produk pertama kali ditambahkan.</small>@endif @error('stockQuantity')<small>{{ $message }}</small>@enderror</label>
                        <label>Stok minimum<input wire:model="minimumStock" type="number" min="0" step="0.001"><small>Pemberitahuan stok menipis muncul saat stok mencapai batas ini. Bisa diubah kapan saja.</small>@error('minimumStock')<small>{{ $message }}</small>@enderror</label>
                        <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="isActive" type="checkbox">Barang aktif</label>
                    </div>
                    <label>Deskripsi<textarea wire:model="description" rows="3" placeholder="Catatan produk (opsional)"></textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
                    <label>Foto barang<input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp">@error('photo')<small>{{ $message }}</small>@enderror</label>
                    <div wire:loading wire:target="photo" class="text-xs text-neutral-500">Mengunggah foto...</div>
                    @if($photo)<img class="product-photo-preview" src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto {{ $name }}">@elseif($currentImagePath)<img class="product-photo-preview" src="{{ asset('storage/'.$currentImagePath) }}" alt="Foto {{ $name }} saat ini" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">@endif
                    @if($currentImagePath)<button class="button-secondary button-small photo-remove-button" wire:click="removeCurrentPhoto" type="button">Hapus foto saat ini</button>@endif
                    <div class="flex justify-end gap-2 border-t border-neutral-200 pt-4"><button class="button-secondary" wire:click="cancel" type="button">Batal</button><button class="button-primary" type="submit">Simpan barang</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($detailsProduct)
        @php($detailStock = (float) ($detailsProduct->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0))
        <div class="catalog-modal-backdrop" wire:key="product-details-{{ $detailsProduct->id }}" wire:click.self="closeDetails">
            <section class="catalog-modal product-detail-modal" role="dialog" aria-modal="true" aria-labelledby="product-detail-title">
                <div class="catalog-panel-heading"><div><h2 id="product-detail-title">Detail barang</h2><p>{{ $detailsProduct->sku }} · {{ $detailsProduct->category->name }}</p></div><button class="button-secondary button-small" wire:click="closeDetails" type="button">Tutup</button></div>
                <div class="product-detail-summary"><img src="{{ $detailsProduct->image_path ? asset('storage/'.$detailsProduct->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $detailsProduct->name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'"><div><h3>{{ $detailsProduct->name }}</h3><p>{{ $detailsProduct->description ?: 'Tidak ada deskripsi.' }}</p><span class="catalog-status {{ $detailStock <= 0 || $detailStock <= (float) $detailsProduct->minimum_stock ? 'catalog-status-off' : 'catalog-status-on' }}">{{ $detailStock <= 0 ? 'Habis' : ($detailStock <= (float) $detailsProduct->minimum_stock ? 'Stok menipis' : 'Tersedia') }}</span></div></div>
                <div class="product-detail-grid"><div><span>Kategori</span><strong>{{ $detailsProduct->category->name }}</strong></div><div><span>Satuan</span><strong>{{ $detailsProduct->unit->name }} ({{ $detailsProduct->unit->abbreviation }})</strong></div><div><span>Harga</span><strong>Diatur pada Atribut Kategori</strong></div><div><span>Stok saat ini</span><strong>@quantity($detailStock) {{ $detailsProduct->pricing_method === 'weight' ? 'kg' : $detailsProduct->unit->abbreviation }}</strong></div><div><span>Stok minimum</span><strong>@quantity($detailsProduct->minimum_stock) {{ $detailsProduct->pricing_method === 'weight' ? 'kg' : $detailsProduct->unit->abbreviation }}</strong></div></div>
                @if($detailsProduct->specificationValues->isNotEmpty())<section class="product-detail-section"><h3>Spesifikasi</h3><div class="product-detail-specs">@foreach($detailsProduct->specificationValues as $value)<div wire:key="detail-spec-{{ $value->id }}"><span>{{ $value->specification->name }}</span><strong>{{ $value->value }}{{ $value->specification->measurement_unit ? ' '.$value->specification->measurement_unit : '' }}</strong></div>@endforeach</div></section>@endif
                <section class="product-detail-section"><h3>Riwayat perubahan stok</h3><div class="table-scroll"><table class="catalog-table"><thead><tr><th>Waktu</th><th>Jenis</th><th>Perubahan</th><th>Saldo</th><th>Pengguna</th><th>Keterangan</th></tr></thead><tbody>@forelse($detailMovements as $movement)<tr wire:key="detail-movement-{{ $movement->id }}"><td>{{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td>{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian', 'sale' => 'Penjualan'][$movement->movement_type] ?? $movement->movement_type }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}@quantity($movement->quantity)</td><td>@quantity($movement->balance_after)</td><td>{{ $movement->user->name }}</td><td>{{ $movement->reference ?: $movement->notes ?: '—' }}</td></tr>@empty<tr><td colspan="6">Belum ada perubahan stok tercatat.</td></tr>@endforelse</tbody></table></div></section>
            </section>
        </div>
    @endif
</div>
