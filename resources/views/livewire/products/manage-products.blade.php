<div class="space-y-5">
    @if (session('status'))<div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>@endif
    <div class="catalog-panel">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="text-base font-semibold text-neutral-900">Katalog barang</h2><p class="mt-1 text-xs text-neutral-500">Data barang, satuan, harga, dan spesifikasi per kategori.</p></div>
            <button class="button-primary" wire:click="create" type="button">+ Barang baru</button>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
            <input class="catalog-input" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, SKU, atau barcode...">
            <select class="catalog-input" wire:model.live="categoryFilter"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        </div>
        <div class="mt-5 overflow-x-auto"><table class="catalog-table"><thead><tr><th>Barang</th><th>Kategori</th><th>Harga</th><th>Satuan</th><th>Stok</th><th>Status</th><th class="text-right">Tindakan</th></tr></thead><tbody>
            @forelse ($products as $product)
                <tr wire:key="product-{{ $product->id }}">
                    <td><div class="flex items-start gap-3"><img class="product-thumb" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'"><div><div class="font-medium text-neutral-900">{{ $product->name }}</div><div class="mt-1 text-xs text-neutral-500">{{ $product->sku }}@if($product->barcode) · {{ $product->barcode }}@endif</div>@if($product->brand)<div class="mt-1 text-xs text-neutral-500">{{ $product->brand->name }}</div>@endif
                    </div></div></td>
                    <td>{{ $product->category->name }}</td>
                    <td><div>Beli @rupiah($product->purchase_price)</div><div class="text-xs text-neutral-500">Jual @rupiah($product->selling_price)</div></td>
                    <td>{{ $product->unit->name }} <span class="text-xs text-neutral-500">({{ $product->unit->abbreviation }})</span></td>
                    @php($stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0))
                    <td>{{ number_format($stock, 3, ',', '.') }} {{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }}</td>
                    <td><div class="product-status-stack"><span class="catalog-status {{ $product->is_active ? 'catalog-status-on' : 'catalog-status-off' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span><span class="catalog-status {{ $stock <= 0 || $stock <= (float) $product->minimum_stock ? 'catalog-status-off' : 'catalog-status-on' }}">{{ $stock <= 0 ? 'Habis' : ($stock <= (float) $product->minimum_stock ? 'Stok menipis' : 'Tersedia') }}</span></div></td>
                    <td><div class="flex justify-end gap-2"><button class="button-secondary button-small" wire:click="viewDetails({{ $product->id }})" type="button">Detail</button><button class="button-secondary button-small" wire:click="edit({{ $product->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="delete({{ $product->id }})" wire:confirm="Arsipkan barang {{ $product->name }}?" type="button">Arsipkan</button></div></td>
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
                        <label>{{ $editingProductId ? 'Stok saat ini' : 'Stok awal' }} ({{ $pricingMethod === 'weight' ? 'kg' : ($pricingMethod === 'bar' ? 'batang' : ($units->firstWhere('id', $unitId)?->abbreviation ?? 'satuan')) }})<input wire:model="stockQuantity" type="number" min="0" step="0.001">@error('stockQuantity')<small>{{ $message }}</small>@enderror</label>
                        <label>Stok minimum<input wire:model="minimumStock" type="number" min="0" step="0.001">@error('minimumStock')<small>{{ $message }}</small>@enderror</label>
                        <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="isActive" type="checkbox">Barang aktif</label>
                    </div>
                    @if($specifications->isNotEmpty())
                        <div class="border-t border-neutral-200 pt-4"><h3 class="mb-3 text-sm font-semibold">Spesifikasi produk · {{ $categories->firstWhere('id', (int) $categoryId)?->name }}</h3><div class="grid gap-4 sm:grid-cols-2">
                            @foreach($specifications as $specification)
                                <label wire:key="specification-field-{{ $specification->id }}">{{ $specification->name }} @if($specification->measurement_unit)<span class="text-neutral-500">({{ $specification->measurement_unit }})</span>@endif @if($specification->is_required)<span class="text-neutral-500">*</span>@endif
                                    @if($specification->options && !$specification->allow_custom)
                                        <select wire:model="specificationValues.{{ $specification->id }}"><option value="">Pilih {{ strtolower($specification->name) }}</option>@foreach($specification->options as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>
                                    @elseif($specification->options && $specification->allow_custom)
                                        <input list="product-spec-options-{{ $specification->id }}" wire:model="specificationValues.{{ $specification->id }}" type="text" placeholder="Pilih atau ketik {{ strtolower($specification->name) }}"><datalist id="product-spec-options-{{ $specification->id }}">@foreach($specification->options as $option)<option value="{{ $option }}">@endforeach</datalist>
                                    @elseif($specification->data_type === 'boolean')
                                        <select wire:model="specificationValues.{{ $specification->id }}"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>
                                    @else
                                        <input wire:model="specificationValues.{{ $specification->id }}" type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif placeholder="Isi {{ strtolower($specification->name) }}">
                                    @endif
                                    @error('specificationValues.'.$specification->id)<small>{{ $message }}</small>@enderror
                                </label>
                            @endforeach
                        </div></div>
                    @endif
                    @if($variantSpecifications->isNotEmpty())
                        <section class="product-variant-admin">
                            <div class="catalog-panel-heading"><div><h3>Harga berdasarkan variasi</h3><p>Pilih kombinasi ukuran yang sudah diatur pada kategori, lalu tetapkan harga jualnya.</p></div><button class="button-secondary button-small" wire:click="addVariant" type="button">Tambah variasi</button></div>
                            @error('variants')<small class="catalog-error">{{ $message }}</small>@enderror
                            @forelse($variants as $variantIndex => $variant)
                                <div class="product-variant-row" wire:key="product-variant-{{ $variant['id'] ?? 'new-'.$variantIndex }}">
                                    @foreach($variantSpecifications as $specification)
                                        <label>{{ $specification->name }}<select wire:model="variants.{{ $variantIndex }}.specifications.{{ $specification->key }}">@foreach($specification->options as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>@error('variants.'.$variantIndex.'.specifications.'.$specification->key)<small>{{ $message }}</small>@enderror</label>
                                    @endforeach
                                    <label>Harga jual varian (Rp)<input type="number" min="0" step="0.01" wire:model="variants.{{ $variantIndex }}.selling_price" placeholder="{{ $sellingPrice }}">@error('variants.'.$variantIndex.'.selling_price')<small>{{ $message }}</small>@enderror</label>
                                    <label class="variant-active-label"><input type="checkbox" wire:model="variants.{{ $variantIndex }}.is_active"> Aktif</label>
                                    <button class="button-danger button-small" wire:click="removeVariant({{ $variantIndex }})" type="button">Hapus variasi</button>
                                </div>
                            @empty
                                <p class="empty-state product-variant-empty">Belum ada harga variasi. Produk memakai harga jual dasar sampai variasi dikonfigurasi.</p>
                            @endforelse
                        </section>
                    @endif
                    <label>Deskripsi<textarea wire:model="description" rows="3" placeholder="Catatan produk (opsional)"></textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
                    <label>Foto barang<input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp">@error('photo')<small>{{ $message }}</small>@enderror</label>
                    <div wire:loading wire:target="photo" class="text-xs text-neutral-500">Mengunggah foto...</div>
                    @if($photo)<img class="product-photo-preview" src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto {{ $name }}">@elseif($currentImagePath)<img class="product-photo-preview" src="{{ asset('storage/'.$currentImagePath) }}" alt="Foto {{ $name }} saat ini" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">@endif
                    @if($currentImagePath)<button class="button-secondary button-small photo-remove-button" wire:click="removeCurrentPhoto" type="button">Hapus foto saat ini</button>@endif
                    @error('specificationValues')<small class="catalog-error">{{ $message }}</small>@enderror
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
                <div class="product-detail-grid"><div><span>Kategori</span><strong>{{ $detailsProduct->category->name }}</strong></div><div><span>Satuan</span><strong>{{ $detailsProduct->unit->name }} ({{ $detailsProduct->unit->abbreviation }})</strong></div><div><span>Harga modal</span><strong>@rupiah($detailsProduct->purchase_price)</strong></div><div><span>Harga jual</span><strong>@rupiah($detailsProduct->selling_price)</strong></div><div><span>Stok saat ini</span><strong>{{ number_format($detailStock, 3, ',', '.') }} {{ $detailsProduct->pricing_method === 'weight' ? 'kg' : $detailsProduct->unit->abbreviation }}</strong></div><div><span>Stok minimum</span><strong>{{ number_format($detailsProduct->minimum_stock, 3, ',', '.') }} {{ $detailsProduct->pricing_method === 'weight' ? 'kg' : $detailsProduct->unit->abbreviation }}</strong></div></div>
                @if($detailsProduct->specificationValues->isNotEmpty())<section class="product-detail-section"><h3>Spesifikasi</h3><div class="product-detail-specs">@foreach($detailsProduct->specificationValues as $value)<div wire:key="detail-spec-{{ $value->id }}"><span>{{ $value->specification->name }}</span><strong>{{ $value->value }}{{ $value->specification->measurement_unit ? ' '.$value->specification->measurement_unit : '' }}</strong></div>@endforeach</div></section>@endif
                <section class="product-detail-section"><h3>Riwayat perubahan stok</h3><div class="table-scroll"><table class="catalog-table"><thead><tr><th>Waktu</th><th>Jenis</th><th>Perubahan</th><th>Saldo</th><th>Pengguna</th><th>Keterangan</th></tr></thead><tbody>@forelse($detailMovements as $movement)<tr wire:key="detail-movement-{{ $movement->id }}"><td>{{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td>{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian', 'sale' => 'Penjualan'][$movement->movement_type] ?? $movement->movement_type }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 3, ',', '.') }}</td><td>{{ number_format($movement->balance_after, 3, ',', '.') }}</td><td>{{ $movement->user->name }}</td><td>{{ $movement->reference ?: $movement->notes ?: '—' }}</td></tr>@empty<tr><td colspan="6">Belum ada perubahan stok tercatat.</td></tr>@endforelse</tbody></table></div></section>
            </section>
        </div>
    @endif
</div>
