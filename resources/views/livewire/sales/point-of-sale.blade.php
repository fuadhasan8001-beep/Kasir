<div class="pos-page">
    @if (session('status'))
        <div class="notice-card"><div><strong>{{ session('status') }}</strong></div></div>
    @endif

    @if ($completedInvoice && $completedReceipt)
        <section class="receipt-ready no-print">
            <div><strong>Transaksi berhasil disimpan</strong><p>Nomor transaksi {{ $completedInvoice }}</p></div>
            <button class="button-secondary" type="button" onclick="window.print()">Cetak struk</button>
        </section>
        <section class="print-receipt">
            <img class="print-receipt-logo" src="{{ asset('images/logo-gjs.png') }}" alt="Gunawan Jaya Steel">
            <h2>Gunawan Jaya Steel</h2>
            <p>Struk penjualan · {{ $completedInvoice }}</p>
            @foreach($completedReceipt['items'] as $receiptItem)
                <div><span>{{ $receiptItem['name'] }} · @quantity($receiptItem['quantity']) {{ $receiptItem['unit'] }}@if($receiptItem['weight']) · @quantity($receiptItem['weight']) kg @endif</span><b>@rupiah($receiptItem['total'])</b></div>
            @endforeach
            <hr>
            <div><strong>Total</strong><strong>@rupiah($completedReceipt['total'])</strong></div>
            <div><span>Tunai diterima</span><b>@rupiah($completedReceipt['received'])</b></div>
            <div><span>Kembalian</span><b>@rupiah($completedReceipt['change'])</b></div>
        </section>
    @endif

    @if($isCartPage)
        <div class="cart-page-heading"><a class="button-secondary button-small" href="{{ route('kasir') }}">← Kembali ke katalog</a><div><h2>Periksa keranjang</h2><p>Ubah jumlah atau spesifikasi sebelum pembayaran tunai.</p></div></div>
        @include('livewire.sales.cart-panel')
    @else
        <div class="pos-catalog-toolbar">
            <div class="pos-catalog-heading"><span class="pos-eyebrow">Penjualan langsung</span><h2>Katalog produk</h2><p>Pilih barang untuk memulai transaksi. Harga dan stok tampil di setiap produk.</p></div>
            <a class="button-primary cart-page-link" href="{{ route('kasir.cart') }}" aria-label="Buka keranjang, {{ count($cart) }} jenis produk">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span>Keranjang</span><b>{{ count($cart) }}</b>
            </a>
        </div>
        <section class="catalog-panel pos-products-panel pos-catalog-panel">
            <div class="catalog-panel-heading">
                <div><h2>Semua produk</h2><p>Ketuk kartu untuk memilih jumlah dan spesifikasi.</p></div>
                <span class="pos-item-count">{{ $products->count() }} tersedia</span>
            </div>

            <label class="search-label pos-search-label"><span>Cari produk</span><span class="pos-search-field"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg><input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, SKU, atau barcode"></span></label>

            <div class="pos-category-section">
                <span class="pos-filter-caption">Kategori</span>
                <div class="category-chips" aria-label="Filter kategori">
                    <button type="button" class="category-chip {{ $categoryFilter === '' ? 'active' : '' }}" wire:click="$set('categoryFilter', '')">Semua</button>
                    @foreach($categories as $category)
                        <button type="button" wire:key="category-chip-{{ $category->id }}" class="category-chip {{ (string) $categoryFilter === (string) $category->id ? 'active' : '' }}" wire:click="$set('categoryFilter', '{{ $category->id }}')">{{ $category->name }}</button>
                    @endforeach
                </div>
            </div>

            @error('product-selection')<p class="catalog-error pos-selection-error">{{ $message }}</p>@enderror

            <div class="pos-product-grid">
                @forelse ($products as $product)
                    @php
                        $stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0);
                    @endphp
                    <button class="pos-product-card {{ $productId === $product->id ? 'selected' : '' }} {{ $stock <= 0 ? 'out-of-stock' : '' }}" type="button" wire:key="pos-product-{{ $product->id }}" wire:click="addProduct({{ $product->id }})" aria-label="{{ $stock <= 0 ? 'Lihat detail produk, stok habis: ' : 'Lihat detail produk: ' }}{{ $product->name }}">
                        <span class="pos-product-photo-wrap">
                            <img class="pos-product-image" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">
                            @if($stock <= 0)<span class="pos-stock-badge stock-empty">Stok habis</span>@endif
                        </span>
                        <span class="pos-product-card-body">
                            <span class="pos-product-copy"><strong>{{ $product->name }}</strong><small>{{ $product->pricing_method === 'weight' ? 'per kg' : ($product->pricing_method === 'bar' ? 'per batang' : 'per '.$product->unit->abbreviation) }}</small><b>{{ $product->category->dimension_sets ? 'Harga sesuai ukuran' : 'Pilih ukuran' }}</b></span>
                            <span class="pos-product-action" aria-hidden="true">+</span>
                        </span>
                    </button>
                @empty
                    <div class="pos-empty-state"><strong>Produk belum tersedia</strong><p>Produk aktif akan muncul di sini setelah ditambahkan melalui Data Barang.</p></div>
                @endforelse
            </div>
        </section>
    @endif

    @if($showProductDetails && $selectedProduct)
        @php
            $selectedStock = (float) ($selectedProduct->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0);
            $specificationsComplete = true;
            $selectedSpecificationValues = [];
            foreach ($selectedProduct->category->specifications as $specification) {
                $isOptionalDimension = in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true);
                $selection = $specificationInputs[$specification->key] ?? '';
                $isCustomSelection = $selection === '__custom__';
                $specificationValue = $isCustomSelection
                    ? trim($customSpecificationInputs[$specification->key] ?? '')
                    : trim($selection);
                if ($specification->is_required && ! $isOptionalDimension && $specificationValue === '') {
                    $specificationsComplete = false;
                }
                if ($specificationValue !== '') {
                    $selectedSpecificationValues[$specification->key] = $specificationValue;
                }
            }
            $selectedSpecificationValues = collect($selectedSpecificationValues)->sortKeys()->all();
            $hasCustomSpecification = collect($selectedProduct->category->specifications)->contains(fn ($specification) => ($specificationInputs[$specification->key] ?? '') === '__custom__');
            $hasCustomNonDimensionSpecification = collect($selectedProduct->category->specifications)
                ->contains(fn ($specification) => ! in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true) && ($specificationInputs[$specification->key] ?? '') === '__custom__');
            $matchedVariant = $hasCustomSpecification ? null : $selectedProduct->variants->first(function ($variant) use ($selectedSpecificationValues) {
                $variantSpecifications = collect($variant->specifications)->sortKeys()->all();

                return $variant->is_active
                    && $variantSpecifications !== []
                    && collect($selectedSpecificationValues)->only(array_keys($variantSpecifications))->sortKeys()->all() === $variantSpecifications;
            });
            $hasActiveVariants = $selectedProduct->variants->contains(fn ($variant) => $variant->is_active);
            $hasIncompleteCustomSelection = collect($selectedProduct->category->specifications)->contains(fn ($specification) => ($specificationInputs[$specification->key] ?? '') === '__custom__' && blank($customSpecificationInputs[$specification->key] ?? ''));
            $selectedUnitPrice = $matchedVariant ? (float) $matchedVariant->selling_price : (float) $selectedProduct->selling_price;
            $usesDimensionPrices = collect($dimensionSets)->contains(fn ($set) => filled($set['diameter'] ?? null));
            $hasConfiguredSizeOptions = collect($dimensionSets)->contains(fn ($set) => filled($set['shape'] ?? null) || filled($set['length'] ?? null) || filled($set['width'] ?? null));
            $selectedDiameter = trim($selectedSpecificationValues['diameter_besi'] ?? '');
            $selectedDimensionSet = collect($dimensionSets)->first(fn ($set) => $selectedDiameter !== ''
                && mb_strtolower(trim((string) ($set['diameter'] ?? ''))) === mb_strtolower($selectedDiameter));
            $dimensionPriceAvailable = is_numeric($selectedDimensionSet['price'] ?? null) && (float) $selectedDimensionSet['price'] > 0;
            if ($usesDimensionPrices) {
                $selectedUnitPrice = $dimensionPriceAvailable ? (float) $selectedDimensionSet['price'] : 0;
            }
            $amountForTotal = $selectedProduct->pricing_method === 'weight' ? (float) ($actualWeight ?: 0) : (float) ($quantity ?: 0);
            $canShowPrice = $specificationsComplete
                && $amountForTotal > 0
                && (! $hasConfiguredSizeOptions || str_starts_with($sizePresetId, 'dimension:') || $sizePresetId === '__custom__')
                && (! $hasActiveVariants || $matchedVariant !== null || $hasCustomSpecification)
                && (! $usesDimensionPrices || $dimensionPriceAvailable)
                && ! $hasIncompleteCustomSelection;
            $estimatedTotal = $amountForTotal * $selectedUnitPrice;
        @endphp
        <div class="catalog-modal-backdrop" wire:key="product-details-modal" wire:click.self="clearProductSelection">
            <section class="catalog-modal pos-product-modal" role="dialog" aria-modal="true" aria-labelledby="product-details-title">
                <div class="catalog-panel-heading">
                    <div><h2 id="product-details-title">{{ $editingCartKey ? 'Edit produk di keranjang' : 'Spesifikasi produk' }}</h2><p>Periksa jumlah dan harga sebelum masuk keranjang.</p></div>
                    <button class="button-secondary button-small" type="button" wire:click="clearProductSelection" aria-label="Batal">Batal</button>
                </div>
                <div class="pos-selected-product">
                    <img src="{{ $selectedProduct->image_path ? asset('storage/'.$selectedProduct->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $selectedProduct->name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">
                    <div><strong>{{ $selectedProduct->name }}</strong><small>{{ $selectedProduct->sku }} · {{ $selectedProduct->category->name }}</small>@if($selectedProduct->brand)<small>{{ $selectedProduct->brand->name }}</small>@endif@if($selectedProduct->description)<small>{{ $selectedProduct->description }}</small>@endif<small>Harga mengikuti ukuran yang dipilih di Atribut Kategori.</small><small>Stok: @quantity($selectedStock) {{ $selectedProduct->pricing_method === 'weight' ? 'kg' : $selectedProduct->unit->abbreviation }}</small></div>
                </div>
                @if($selectedStock <= 0)<p class="catalog-error">Stok barang ini habis. Detail tetap bisa dilihat, tetapi barang tidak dapat ditambahkan ke transaksi.</p>@endif
                <form class="catalog-form pos-item-form" wire:submit="addItem">
                    @if($selectedProduct->category->specifications->isNotEmpty())
                        <section class="pos-size-picker" aria-labelledby="pos-size-picker-title">
                            <div class="pos-size-picker-heading"><strong id="pos-size-picker-title">Pilih ukuran atau spesifikasi barang</strong><small>Daftar dari kategori {{ $selectedProduct->category->name }}</small></div>
                            <div class="pos-input-grid">
                                @if($dimensionSets !== [])
                                    <div class="pos-spec-option-group pos-size-preset-group">
                                        <span class="pos-spec-option-label">Ukuran</span>
                                        <div class="pos-spec-options" role="group" aria-label="Pilihan ukuran barang">
                                            @foreach($dimensionSets as $optionIndex => $dimensionSet)
                                                @if(filled($dimensionSet['shape'] ?? null) || filled($dimensionSet['length'] ?? null) || filled($dimensionSet['width'] ?? null))
                                                @php
                                                    $dimensionLabel = collect([
                                                        filled($dimensionSet['shape'] ?? '') ? $dimensionSet['shape'] : null,
                                                        filled($dimensionSet['length'] ?? '') ? 'P '.$dimensionSet['length'] : null,
                                                        filled($dimensionSet['width'] ?? '') ? 'L '.$dimensionSet['width'] : null,
                                                    ])->filter()->implode(', ');
                                                @endphp
                                                <button type="button" wire:key="pos-size-preset-{{ $optionIndex }}" class="pos-spec-option pos-size-preset {{ $sizePresetId === 'dimension:'.$optionIndex ? 'active' : '' }}" wire:click="selectSizePreset('dimension:{{ $optionIndex }}')" aria-label="Pilih ukuran {{ $optionIndex + 1 }}: {{ $dimensionLabel }}">{{ $dimensionLabel }}</button>
                                                @endif
                                            @endforeach
                                            <button type="button" class="pos-spec-option pos-size-preset pos-custom-size-preset {{ $sizePresetId === '__custom__' ? 'active' : '' }}" wire:click="selectSizePreset('__custom__')">Custom</button>
                                        </div>
                                        @if(collect($dimensionSets)->contains(fn ($set) => filled($set['diameter'] ?? null)))
                                            <span class="pos-spec-option-label mt-3">Diameter besi · harga/kg</span>
                                            <div class="pos-spec-options" role="group" aria-label="Pilihan diameter besi">
                                                @php($listedDiameters = [])
                                                @foreach($dimensionSets as $diameterIndex => $diameterSet)
                                                    @if(filled($diameterSet['diameter'] ?? null) && !in_array(mb_strtolower($diameterSet['diameter']), $listedDiameters, true))
                                                        @php($listedDiameters[] = mb_strtolower($diameterSet['diameter']))
                                                        <button type="button" wire:key="pos-diameter-preset-{{ $diameterIndex }}" class="pos-spec-option {{ $diameterPresetId === 'diameter:'.$diameterIndex ? 'active' : '' }}" wire:click="selectDiameterPreset('diameter:{{ $diameterIndex }}')" aria-label="Pilih diameter {{ $diameterSet['diameter'] }} dengan harga @rupiah($diameterSet['price'] ?? 0) per kg">{{ $diameterSet['diameter'] }} · @rupiah($diameterSet['price'] ?? 0)/kg</button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @error('sizePresetId')<small>{{ $message }}</small>@enderror
                                        @if($sizePresetId === '__custom__')
                                            <div class="pos-custom-size-fields">
                                                @foreach($selectedProduct->category->specifications->whereIn('key', ['bentuk', 'panjang', 'lebar', 'diameter_besi']) as $dimensionSpecification)
                                                    <label>{{ $dimensionSpecification->name }}@if($dimensionSpecification->measurement_unit) ({{ $dimensionSpecification->measurement_unit }})@endif<input type="{{ in_array($dimensionSpecification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($dimensionSpecification->data_type === 'number')step="any"@elseif($dimensionSpecification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="customSpecificationInputs.{{ $dimensionSpecification->key }}" maxlength="500" placeholder="Masukkan {{ strtolower($dimensionSpecification->name) }} custom">@error('specificationInputs.'.$dimensionSpecification->key)<small>{{ $message }}</small>@enderror</label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @foreach($selectedProduct->category->specifications as $specification)
                                    @if($dimensionSets !== [] && in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true))
                                        @continue
                                    @endif
                                    @php($specificationSelection = $specificationInputs[$specification->key] ?? '')
                                    <div class="pos-spec-option-group" wire:key="pos-spec-{{ $specification->id }}"><span class="pos-spec-option-label">{{ $specification->name }}@if($specification->measurement_unit) ({{ $specification->measurement_unit }})@endif @if($specification->is_required && ! in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true))<span aria-hidden="true">*</span>@endif</span>
                                        @if(filled($specification->options))
                                            <div class="pos-spec-options" role="group" aria-label="{{ $specification->name }}">
                                                @foreach($specification->options as $optionIndex => $option)
                                                    <button type="button" wire:key="pos-spec-option-{{ $specification->id }}-{{ $optionIndex }}" class="pos-spec-option {{ $specificationSelection === (string) $option ? 'active' : '' }}" wire:click="$set('specificationInputs.{{ $specification->key }}', @js((string) $option))">{{ $option }}</button>
                                                @endforeach
                                                @if($specification->allow_custom)<button type="button" class="pos-spec-option {{ $specificationSelection === '__custom__' ? 'active' : '' }}" wire:click="$set('specificationInputs.{{ $specification->key }}', '__custom__')">Custom</button>@endif
                                            </div>
                                        @elseif($specification->data_type === 'boolean')
                                            <select wire:model.live="specificationInputs.{{ $specification->key }}"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>
                                        @else
                                            <input type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="specificationInputs.{{ $specification->key }}" maxlength="500" placeholder="Isi {{ strtolower($specification->name) }}">
                                        @endif
                                        @if($specificationSelection === '__custom__')
                                            <input type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="customSpecificationInputs.{{ $specification->key }}" maxlength="500" placeholder="Masukkan {{ strtolower($specification->name) }} custom">
                                        @endif
                                        @error('specificationInputs.'.$specification->key)<small>{{ $message }}</small>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                    @if($hasCustomNonDimensionSpecification)
                        <label>Catatan tambahan<textarea rows="2" wire:model="orderNotes" placeholder="Catatan untuk transaksi"></textarea></label>
                    @endif
                    <div class="pos-input-grid">
                        @if($selectedProduct->pricing_method !== 'weight')
                            <label>Jumlah {{ $selectedProduct->pricing_method === 'bar' ? 'batang' : $selectedProduct->unit->abbreviation }}<input type="number" min="0.001" step="0.001" wire:model.live.debounce.250ms="quantity" placeholder="Masukkan jumlah">@error('quantity')<small>{{ $message }}</small>@enderror</label>
                        @else
                            <label>Berat aktual (kg)<input type="number" min="0.001" step="0.001" wire:model.live.debounce.250ms="actualWeight" placeholder="Masukkan berat">@error('actualWeight')<small>{{ $message }}</small>@enderror</label>
                        @endif
                    </div>
                    @error('productId')<small class="catalog-error">{{ $message }}</small>@enderror
                    @error('specificationInputs')<small class="catalog-error">{{ $message }}</small>@enderror
                    @if($canShowPrice)
                        <div class="pos-estimated-total"><span>Harga satuan · {{ $selectedProduct->pricing_method === 'weight' ? 'kg' : ($selectedProduct->pricing_method === 'bar' ? 'batang' : $selectedProduct->unit->abbreviation) }}</span><strong>@rupiah($selectedUnitPrice)</strong></div>
                        <div class="pos-estimated-total"><span>Subtotal</span><strong>@rupiah($estimatedTotal)</strong></div>
                    @elseif($hasActiveVariants && $specificationsComplete && !$hasCustomSpecification)
                        <small class="catalog-error">Harga untuk ukuran ini belum diatur admin. Konfigurasikan harga variasi atau custom sebelum melanjutkan.</small>
                    @elseif($usesDimensionPrices && $specificationsComplete && ! $dimensionPriceAvailable)
                        <small class="catalog-error">Harga ukuran atau diameter ini belum tersedia. Lengkapi harga per kg di Atribut Kategori.</small>
                    @endif
                    <button class="button-primary add-item-button" type="submit" wire:loading.attr="disabled" wire:target="addItem" @disabled($selectedStock <= 0)>{{ $editingCartKey ? 'Simpan perubahan' : 'Tambahkan ke Keranjang' }}</button>
                </form>
            </section>
        </div>
    @endif

    @if($showConfirmation)
        <div class="catalog-modal-backdrop" wire:key="checkout-confirmation">
            <section class="catalog-modal checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
                <div class="catalog-panel-heading"><div><h2 id="checkout-title">Pembayaran tunai</h2><p>Periksa total transaksi sebelum menyimpan pembayaran.</p></div><button class="button-secondary button-small" type="button" wire:click="cancelCheckout" aria-label="Tutup">Tutup</button></div>
                <div class="confirmation-summary">
                    @foreach($cartForDisplay as $item)
                        <div><span>{{ $item['product_name'] }} · @if($item['pricing_basis'] === 'weight')@quantity($item['actual_weight']) kg @else@quantity($item['quantity']) {{ $item['unit_name'] }}@endif</span><strong>@if($item['price_available'])@rupiah($item['line_total'])@else Harga tidak tersedia @endif</strong></div>
                    @endforeach
                    <div class="confirmation-total"><span>Total tagihan</span><strong>@rupiah($subtotal)</strong></div>
                </div>
                <label class="cash-input-label">Uang tunai diterima<input class="catalog-input" type="number" min="{{ $subtotal }}" step="1" wire:model.live="amountReceived" placeholder="Masukkan nominal">@error('amountReceived')<small class="catalog-error">{{ $message }}</small>@enderror</label>
                <div class="change-summary"><span>Kembalian</span><strong>@rupiah(max(0, (float) $amountReceived - $subtotal))</strong></div>
                <div class="modal-actions"><button class="button-secondary" type="button" wire:click="cancelCheckout">Kembali</button><button class="button-primary" type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout">Konfirmasi &amp; simpan</button></div>
            </section>
        </div>
    @endif
</div>
