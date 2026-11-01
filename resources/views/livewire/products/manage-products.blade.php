<div class="space-y-5">
    @if (session('status'))<div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>@endif
    <div class="catalog-panel">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="text-base font-semibold text-neutral-900">Katalog barang</h2><p class="mt-1 text-xs text-neutral-500">Data barang, satuan, harga, dan spesifikasi per kategori.</p></div>
            @if ($canManage)<button class="button-primary" wire:click="create" type="button">+ Barang baru</button>@endif
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
            <input class="catalog-input" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, SKU, atau barcode...">
            <select class="catalog-input" wire:model.live="categoryFilter"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        </div>
        <div class="mt-5 overflow-x-auto"><table class="catalog-table"><thead><tr><th>Barang</th><th>Kategori</th><th>Harga</th><th>Satuan</th><th>Status</th>@if($canManage)<th class="text-right">Tindakan</th>@endif</tr></thead><tbody>
            @forelse ($products as $product)
                <tr wire:key="product-{{ $product->id }}">
                    <td><div class="font-medium text-neutral-900">{{ $product->name }}</div><div class="mt-1 text-xs text-neutral-500">{{ $product->sku }}@if($product->barcode) · {{ $product->barcode }}@endif</div>@if($product->brand)<div class="mt-1 text-xs text-neutral-500">{{ $product->brand->name }}</div>@endif
                        @if($product->specificationValues->isNotEmpty())<div class="mt-2 flex flex-wrap gap-1">@foreach($product->specificationValues as $specificationValue)<span class="catalog-tag" wire:key="product-{{ $product->id }}-spec-{{ $specificationValue->id }}">{{ $specificationValue->specification->name }}: {{ $specificationValue->value }}{{ $specificationValue->specification->measurement_unit ? ' '.$specificationValue->specification->measurement_unit : '' }}</span>@endforeach</div>@endif
                    </td>
                    <td>{{ $product->category->name }}</td>
                    <td><div>Beli Rp{{ number_format((float) $product->purchase_price, 0, ',', '.') }}</div><div class="text-xs text-neutral-500">Jual Rp{{ number_format((float) $product->selling_price, 0, ',', '.') }}</div></td>
                    <td>{{ $product->unit->name }} <span class="text-xs text-neutral-500">({{ $product->unit->abbreviation }})</span></td>
                    <td><span class="catalog-status {{ $product->is_active ? 'catalog-status-on' : 'catalog-status-off' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    @if($canManage)<td><div class="flex justify-end gap-2"><button class="button-secondary button-small" wire:click="edit({{ $product->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="delete({{ $product->id }})" wire:confirm="Arsipkan barang {{ $product->name }}?" type="button">Arsip</button></div></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="py-10 text-center text-neutral-500">Tidak ada barang yang cocok dengan pencarian.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>

    @if ($showForm)
        <div class="catalog-modal-backdrop">
            <section class="catalog-modal" role="dialog" aria-modal="true" aria-labelledby="product-form-title">
                <div class="catalog-panel-heading"><div><h2 id="product-form-title">{{ $editingProductId ? 'Ubah barang' : 'Tambah barang' }}</h2><p>Harga disimpan dalam Rupiah. Stok dikelola pada tahap inventaris.</p></div><button class="button-secondary button-small" wire:click="cancel" type="button">Tutup</button></div>
                <form wire:submit="save" class="catalog-form mt-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label>SKU / kode barang<input wire:model="sku" type="text" maxlength="80" placeholder="BRG-0001">@error('sku')<small>{{ $message }}</small>@enderror</label>
                        <label>Barcode<input wire:model="barcode" type="text" maxlength="100" placeholder="Opsional">@error('barcode')<small>{{ $message }}</small>@enderror</label>
                        <label>Nama barang<input wire:model="name" type="text" maxlength="180" placeholder="Nama produk">@error('name')<small>{{ $message }}</small>@enderror</label>
                        <label>Merek<input wire:model="brandName" type="text" maxlength="120" placeholder="Merek (opsional)">@error('brandName')<small>{{ $message }}</small>@enderror</label>
                        <label>Kategori<select wire:model.live="categoryId"><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('categoryId')<small>{{ $message }}</small>@enderror</label>
                        <label>Satuan dasar<select wire:model="unitId"><option value="">Pilih satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>@endforeach</select>@error('unitId')<small>{{ $message }}</small>@enderror</label>
                        <label>Jenis produk<select wire:model="productType"><option value="general">Barang umum</option><option value="construction_material">Bahan konstruksi</option><option value="assembled">Produk rakitan</option></select>@error('productType')<small>{{ $message }}</small>@enderror</label>
                        <label>Metode harga<select wire:model="pricingMethod"><option value="unit">Per satuan</option><option value="weight">Per kilogram</option><option value="bar">Per batang</option></select>@error('pricingMethod')<small>{{ $message }}</small>@enderror</label>
                        <label>Harga beli (Rp{{ $pricingMethod === 'weight' ? '/kg' : ($pricingMethod === 'bar' ? '/batang' : '') }})<input wire:model="purchasePrice" type="number" min="0" step="0.01">@error('purchasePrice')<small>{{ $message }}</small>@enderror</label>
                        <label>Harga jual (Rp{{ $pricingMethod === 'weight' ? '/kg' : ($pricingMethod === 'bar' ? '/batang' : '') }})<input wire:model="sellingPrice" type="number" min="0" step="0.01">@error('sellingPrice')<small>{{ $message }}</small>@enderror</label>
                        <label>Stok minimum<input wire:model="minimumStock" type="number" min="0" step="0.001">@error('minimumStock')<small>{{ $message }}</small>@enderror</label>
                        <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="isActive" type="checkbox">Barang aktif</label>
                    </div>
                    @if($specifications->isNotEmpty())
                        <div class="border-t border-neutral-200 pt-4"><h3 class="mb-3 text-sm font-semibold">Spesifikasi · {{ $categories->firstWhere('id', (int) $categoryId)?->name }}</h3><div class="grid gap-4 sm:grid-cols-2">
                            @foreach($specifications as $specification)
                                <label wire:key="specification-field-{{ $specification->id }}">{{ $specification->name }} @if($specification->measurement_unit)<span class="text-neutral-500">({{ $specification->measurement_unit }})</span>@endif @if($specification->is_required)<span class="text-neutral-500">*</span>@endif
                                    @if($specification->data_type === 'boolean')<select wire:model="specificationValues.{{ $specification->id }}"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>@else<input wire:model="specificationValues.{{ $specification->id }}" type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif placeholder="Isi {{ strtolower($specification->name) }}">@endif
                                    @error('specificationValues.'.$specification->id)<small>{{ $message }}</small>@enderror
                                </label>
                            @endforeach
                        </div></div>
                    @endif
                    <label>Deskripsi<textarea wire:model="description" rows="3" placeholder="Catatan produk (opsional)"></textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
                    @error('specificationValues')<small class="catalog-error">{{ $message }}</small>@enderror
                    <div class="flex justify-end gap-2 border-t border-neutral-200 pt-4"><button class="button-secondary" wire:click="cancel" type="button">Batal</button><button class="button-primary" type="submit">Simpan barang</button></div>
                </form>
            </section>
        </div>
    @endif
</div>
