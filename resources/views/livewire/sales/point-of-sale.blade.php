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
                <div><span>{{ $receiptItem['name'] }} · {{ number_format($receiptItem['quantity'], 3, ',', '.') }} {{ $receiptItem['unit'] }}@if($receiptItem['weight']) · {{ number_format($receiptItem['weight'], 3, ',', '.') }} kg @endif</span><b>@rupiah($receiptItem['total'])</b></div>
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
            <div><h2>Katalog produk</h2><p>Pilih produk untuk memulai transaksi.</p></div>
            <a class="button-primary cart-page-link" href="{{ route('kasir.cart') }}" aria-label="Buka keranjang, {{ count($cart) }} jenis produk">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span>Keranjang</span><b>{{ count($cart) }}</b>
            </a>
        </div>
        <section class="catalog-panel pos-products-panel">
            <div class="catalog-panel-heading">
                <div><h2>Katalog Produk</h2><p>Pilih produk untuk menambahkannya ke transaksi.</p></div>
                <span class="pos-item-count">{{ $products->count() }} produk</span>
            </div>

            <label class="search-label"><span>Cari produk</span><input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, SKU, atau barcode"></label>

            <div class="category-chips" aria-label="Filter kategori">
                <button type="button" class="category-chip {{ $categoryFilter === '' ? 'active' : '' }}" wire:click="$set('categoryFilter', '')">Semua</button>
                @foreach($categories as $category)
                    <button type="button" wire:key="category-chip-{{ $category->id }}" class="category-chip {{ (string) $categoryFilter === (string) $category->id ? 'active' : '' }}" wire:click="$set('categoryFilter', '{{ $category->id }}')">{{ $category->name }}</button>
                @endforeach
            </div>

            @error('product-selection')<p class="catalog-error pos-selection-error">{{ $message }}</p>@enderror

            <div class="pos-product-grid">
                @forelse ($products as $product)
                    @php
                        $stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0);
                    @endphp
                    <button class="pos-product-card {{ $productId === $product->id ? 'selected' : '' }} {{ $stock <= 0 ? 'out-of-stock' : '' }}" type="button" wire:key="pos-product-{{ $product->id }}" wire:click="addProduct({{ $product->id }})" @disabled($stock <= 0) aria-label="{{ $stock <= 0 ? 'Stok habis: ' : 'Pilih produk: ' }}{{ $product->name }}">
                        <span class="pos-product-photo-wrap">
                            <img class="pos-product-image" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">
                            @if($stock <= 0)<span class="pos-stock-badge stock-empty">Stok habis</span>@endif
                        </span>
                        <span class="pos-product-card-body">
                            <span class="pos-product-copy"><strong>{{ $product->name }}</strong><small>{{ $product->pricing_method === 'weight' ? 'per kg' : ($product->pricing_method === 'bar' ? 'per batang' : 'per '.$product->unit->abbreviation) }}</small><b>@rupiah($product->selling_price)</b></span>
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
            $specificationsComplete = true;
            $selectedSpecificationValues = [];
            foreach ($selectedProduct->category->specifications as $specification) {
                $selection = $specificationInputs[$specification->key] ?? '';
                $isCustomSelection = $selection === '__custom__';
                $specificationValue = $isCustomSelection
                    ? trim($customSpecificationInputs[$specification->key] ?? '')
                    : trim($selection);
                if ($specification->is_required && $specificationValue === '') {
                    $specificationsComplete = false;
                }
                if ($specificationValue !== '') {
                    $selectedSpecificationValues[$specification->key] = $specificationValue;
                }
            }
            $selectedSpecificationValues = collect($selectedSpecificationValues)->sortKeys()->all();
            $matchedVariant = $selectedProduct->variants->first(function ($variant) use ($selectedSpecificationValues) {
                $variantSpecifications = collect($variant->specifications)->sortKeys()->all();

                return $variant->is_active
                    && $variantSpecifications !== []
                    && collect($selectedSpecificationValues)->only(array_keys($variantSpecifications))->sortKeys()->all() === $variantSpecifications;
            });
            $hasActiveVariants = $selectedProduct->variants->contains(fn ($variant) => $variant->is_active);
            $hasIncompleteCustomSelection = collect($selectedProduct->category->specifications)->contains(fn ($specification) => ($specificationInputs[$specification->key] ?? '') === '__custom__' && blank($customSpecificationInputs[$specification->key] ?? ''));
            $selectedUnitPrice = $matchedVariant ? (float) $matchedVariant->selling_price : (float) $selectedProduct->selling_price;
            $amountForTotal = $selectedProduct->pricing_method === 'weight' ? (float) ($actualWeight ?: 0) : (float) ($quantity ?: 0);
            $canShowPrice = $specificationsComplete
                && $amountForTotal > 0
                && (! $hasActiveVariants || $matchedVariant !== null)
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
                    <div><strong>{{ $selectedProduct->name }}</strong><small>{{ $selectedProduct->category->name }}</small></div>
                </div>
                <form class="catalog-form pos-item-form" wire:submit="addItem">
                    @if($selectedProduct->category->specifications->isNotEmpty())
                        <div class="pos-input-grid">
                            @foreach($selectedProduct->category->specifications as $specification)
                                <label wire:key="pos-spec-{{ $specification->id }}">{{ $specification->name }}@if($specification->measurement_unit) ({{ $specification->measurement_unit }})@endif @if($specification->is_required)<span aria-hidden="true">*</span>@endif
                                    @if(filled($specification->options))
                                        <select wire:model.live="specificationInputs.{{ $specification->key }}">
                                            <option value="">Pilih {{ strtolower($specification->name) }}</option>
                                            @foreach($specification->options as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                            @if($specification->allow_custom)<option value="__custom__">Ukuran custom</option>@endif
                                        </select>
                                        @if($specification->allow_custom && ($specificationInputs[$specification->key] ?? '') === '__custom__')
                                            <input type="text" wire:model.live.debounce.250ms="customSpecificationInputs.{{ $specification->key }}" maxlength="500" placeholder="Ketik {{ strtolower($specification->name) }}">
                                        @endif
                                    @elseif($specification->data_type === 'boolean')
                                        <select wire:model.live="specificationInputs.{{ $specification->key }}"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>
                                    @else
                                        <input type="text" wire:model.live.debounce.250ms="specificationInputs.{{ $specification->key }}" maxlength="500" placeholder="Masukkan {{ strtolower($specification->name) }}">
                                    @endif
                                    @error('specificationInputs.'.$specification->key)<small>{{ $message }}</small>@enderror
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <div class="pos-input-grid">
                        @if($selectedProduct->pricing_method !== 'weight')
                            <label>Jumlah {{ $selectedProduct->pricing_method === 'bar' ? 'batang' : $selectedProduct->unit->abbreviation }}<input type="number" min="0.001" step="0.001" wire:model.live.debounce.250ms="quantity" placeholder="Masukkan jumlah">@error('quantity')<small>{{ $message }}</small>@enderror</label>
                        @else
                            <label>Berat aktual (kg)<input type="number" min="0.001" step="0.001" wire:model.live.debounce.250ms="actualWeight" placeholder="Masukkan berat">@error('actualWeight')<small>{{ $message }}</small>@enderror</label>
                        @endif
                    </div>
                    <label>Catatan tambahan<textarea rows="2" wire:model="orderNotes" placeholder="Catatan untuk transaksi"></textarea></label>
                    @error('productId')<small class="catalog-error">{{ $message }}</small>@enderror
                    @error('specificationInputs')<small class="catalog-error">{{ $message }}</small>@enderror
                    @if($canShowPrice)
                        <div class="pos-estimated-total"><span>Harga satuan · {{ $selectedProduct->pricing_method === 'weight' ? 'kg' : ($selectedProduct->pricing_method === 'bar' ? 'batang' : $selectedProduct->unit->abbreviation) }}</span><strong>@rupiah($selectedUnitPrice)</strong></div>
                        <div class="pos-estimated-total"><span>Subtotal</span><strong>@rupiah($estimatedTotal)</strong></div>
                    @elseif($hasActiveVariants && $specificationsComplete)
                        <small class="catalog-error">Harga untuk ukuran ini belum diatur admin. Konfigurasikan harga variasi atau custom sebelum melanjutkan.</small>
                    @endif
                    <button class="button-primary add-item-button" type="submit" wire:loading.attr="disabled" wire:target="addItem">{{ $editingCartKey ? 'Simpan perubahan' : 'Tambahkan ke Keranjang' }}</button>
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
                        <div><span>{{ $item['product_name'] }} · @if($item['pricing_basis'] === 'weight'){{ number_format($item['actual_weight'], 3, ',', '.') }} kg @else{{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit_name'] }}@endif</span><strong>@if($item['price_available'])@rupiah($item['line_total'])@else Harga tidak tersedia @endif</strong></div>
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
