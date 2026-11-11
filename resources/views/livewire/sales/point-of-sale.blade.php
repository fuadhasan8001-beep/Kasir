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
            <h2>Gunawan Jaya Steel</h2>
            <p>Struk penjualan · {{ $completedInvoice }}</p>
            @foreach($completedReceipt['items'] as $receiptItem)
                <div><span>{{ $receiptItem['name'] }} · {{ number_format($receiptItem['quantity'], 3, ',', '.') }} {{ $receiptItem['unit'] }}@if($receiptItem['weight']) · {{ number_format($receiptItem['weight'], 3, ',', '.') }} kg @endif</span><b>Rp {{ number_format($receiptItem['total'], 0, ',', '.') }}</b></div>
            @endforeach
            <hr>
            <div><strong>Total</strong><strong>Rp {{ number_format($completedReceipt['total'], 0, ',', '.') }}</strong></div>
            <div><span>Tunai diterima</span><b>Rp {{ number_format($completedReceipt['received'], 0, ',', '.') }}</b></div>
            <div><span>Kembalian</span><b>Rp {{ number_format($completedReceipt['change'], 0, ',', '.') }}</b></div>
        </section>
    @endif

    <div class="pos-workspace">
        <section class="catalog-panel pos-products-panel">
            <div class="catalog-panel-heading">
                <div><h2>Katalog barang</h2><p>Pilih barang untuk mulai mengisi transaksi.</p></div>
                <span class="pos-item-count">{{ $products->count() }} barang</span>
            </div>

            <label class="search-label"><span>Cari barang</span><input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, SKU, atau barcode"></label>

            <div class="category-chips" aria-label="Filter kategori">
                <button type="button" class="category-chip {{ $categoryFilter === '' ? 'active' : '' }}" wire:click="$set('categoryFilter', '')">Semua</button>
                @foreach($categories as $category)
                    <button type="button" wire:key="category-chip-{{ $category->id }}" class="category-chip {{ (string) $categoryFilter === (string) $category->id ? 'active' : '' }}" wire:click="$set('categoryFilter', '{{ $category->id }}')">{{ $category->name }}</button>
                @endforeach
            </div>

            <div class="pos-product-grid">
                @forelse ($products as $product)
                    @php($stock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0))
                    <button class="pos-product-card {{ $productId === $product->id ? 'selected' : '' }}" type="button" wire:key="pos-product-{{ $product->id }}" wire:click="$set('productId', {{ $product->id }})">
                        @if($product->image_path)
                            <img class="pos-product-image" src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <span class="pos-product-image pos-product-placeholder" aria-hidden="true">▦</span>
                        @endif
                        <span class="pos-product-card-body">
                            <strong>{{ $product->name }}</strong>
                            <small>{{ $product->category->name }} · {{ $product->sku }}</small>
                            @if($product->specificationValues->isNotEmpty())<small>{{ $product->specificationValues->take(2)->map(fn ($value) => $value->specification->name.': '.$value->value.($value->specification->measurement_unit ? ' '.$value->specification->measurement_unit : ''))->implode(' · ') }}</small>@endif
                            <span class="pos-product-card-foot"><b>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</b><small class="{{ $stock > 0 ? 'stock-available' : 'stock-empty' }}">{{ number_format($stock, 3, ',', '.') }} {{ $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation }}</small></span>
                        </span>
                    </button>
                @empty
                    <div class="pos-empty-state"><span>⌕</span><strong>Barang tidak ditemukan</strong><p>Coba nama atau kode barang lain, atau pilih kategori berbeda.</p></div>
                @endforelse
            </div>
        </section>

        <section class="catalog-panel pos-checkout-panel">
            <div class="catalog-panel-heading">
                <div><h2>Transaksi baru</h2><p>Masukkan spesifikasi dan harga sesuai kesepakatan.</p></div>
                <span class="cart-badge">{{ count($cart) }}</span>
            </div>

            <label class="customer-field">Nama pembeli <span>(opsional)</span><input class="catalog-input" type="text" wire:model="customerName" maxlength="160" placeholder="Nama pembeli"></label>

            <form class="catalog-form pos-item-form" wire:submit="addItem">
                <label>Barang dipilih
                    <select wire:model.live="productId"><option value="">Pilih barang dari katalog</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>@endforeach</select>
                    @error('productId')<small>{{ $message }}</small>@enderror
                </label>
                @php($selectedProduct = $products->firstWhere('id', $productId))
                @if($selectedProduct)
                    <div class="selected-product-summary"><strong>{{ $selectedProduct->name }}</strong><small>{{ $selectedProduct->category->name }} · Stok {{ number_format((float) ($selectedProduct->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0), 3, ',', '.') }} {{ $selectedProduct->pricing_method === 'weight' ? 'kg' : $selectedProduct->unit->abbreviation }}</small></div>
                @endif
                <div class="pos-input-grid">
                    <label>Jumlah<input type="number" min="0.001" step="0.001" wire:model="quantity" placeholder="1">@error('quantity')<small>{{ $message }}</small>@enderror</label>
                    <label>Berat aktual (kg)<input type="number" min="0.001" step="0.001" wire:model="actualWeight" placeholder="Jika diketahui">@error('actualWeight')<small>{{ $message }}</small>@enderror</label>
                </div>
                <div class="pos-input-grid">
                    <label>Metode harga<select wire:model.live="priceBasis"><option value="weight">Per kilogram</option><option value="unit">Per batang / satuan</option><option value="custom">Harga custom total</option></select></label>
                    <label>{{ $priceBasis === 'weight' ? 'Harga per kg (Rp)' : ($priceBasis === 'custom' ? 'Harga custom (Rp)' : 'Harga per satuan (Rp)') }}<input type="number" min="1" step="1" wire:model="unitPrice" placeholder="Masukkan harga">@error('unitPrice')<small>{{ $message }}</small>@enderror</label>
                </div>
                <div class="pos-input-grid pos-dimensions">
                    <label>Diameter<input type="text" wire:model="diameter" placeholder="Mis. 10 mm"></label>
                    <label>Panjang<input type="text" wire:model="length" placeholder="Mis. 6 m"></label>
                    <label>Lebar<input type="text" wire:model="width" placeholder="Mis. 20 cm"></label>
                </div>
                <label>Ukuran / spesifikasi custom<input type="text" wire:model="customSpecification" maxlength="500" placeholder="Contoh: cakar 40 × 40 cm"></label>
                <label>Catatan tambahan<textarea rows="2" wire:model="orderNotes" placeholder="Catatan untuk transaksi"></textarea></label>
                <button class="button-secondary add-item-button" type="submit" wire:loading.attr="disabled" wire:target="addItem">Tambah barang</button>
            </form>

            <div class="cart-section">
                <div class="cart-heading"><h3>Keranjang</h3>@if($cart)<button class="text-button" type="button" wire:click="clearCart" wire:confirm="Kosongkan semua barang dalam keranjang?">Kosongkan</button>@endif</div>
                <div class="cart-list">
                    @forelse ($cart as $item)
                        <article class="cart-item" wire:key="cart-{{ $item['key'] }}">
                            <div class="cart-item-main"><strong>{{ $item['product_name'] }}</strong><small>{{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit_name'] }}@if($item['actual_weight']) · {{ number_format($item['actual_weight'], 3, ',', '.') }} kg @endif</small>@if($item['specifications'])<small>{{ collect($item['specifications'])->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>@endif@if($item['notes'])<small>{{ $item['notes'] }}</small>@endif</div>
                            <div class="cart-item-actions"><b>Rp {{ number_format($item['line_total'], 0, ',', '.') }}</b><div><button class="text-button" type="button" wire:click="editItem('{{ $item['key'] }}')">Edit</button><button class="text-button text-danger" type="button" wire:click="removeItem('{{ $item['key'] }}')">Hapus</button></div></div>
                        </article>
                    @empty
                        <div class="empty-state">Belum ada barang. Pilih barang dari katalog untuk memulai.</div>
                    @endforelse
                    @error('cart')<small class="catalog-error">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="checkout-summary">
                <div><span>Total item</span><strong>{{ number_format($totalItems, 3, ',', '.') }}</strong></div>
                <div><span>Total berat</span><strong>{{ number_format($totalWeight, 3, ',', '.') }} kg</strong></div>
                <div class="grand-total"><span>Total transaksi</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                <button class="button-primary checkout-button" type="button" wire:click="startCheckout" @disabled(!$cart)>Lanjut ke pembayaran tunai</button>
            </div>
        </section>
    </div>

    @if($showConfirmation)
        <div class="catalog-modal-backdrop" wire:key="checkout-confirmation">
            <section class="catalog-modal checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
                <div class="catalog-panel-heading"><div><h2 id="checkout-title">Konfirmasi transaksi</h2><p>Periksa ringkasan sebelum menyimpan pembayaran tunai.</p></div><button class="button-secondary button-small" type="button" wire:click="cancelCheckout" aria-label="Tutup">Tutup</button></div>
                <div class="confirmation-summary">
                    @foreach($cart as $item)
                        <div><span>{{ $item['product_name'] }} · {{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit_name'] }}</span><strong>Rp {{ number_format($item['line_total'], 0, ',', '.') }}</strong></div>
                    @endforeach
                    <div class="confirmation-total"><span>Total tagihan</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                </div>
                <label class="cash-input-label">Uang tunai diterima<input class="catalog-input" type="number" min="{{ $subtotal }}" step="1" wire:model.live="amountReceived" placeholder="Masukkan nominal">@error('amountReceived')<small class="catalog-error">{{ $message }}</small>@enderror</label>
                <div class="change-summary"><span>Kembalian</span><strong>Rp {{ number_format(max(0, (float) $amountReceived - $subtotal), 0, ',', '.') }}</strong></div>
                <div class="modal-actions"><button class="button-secondary" type="button" wire:click="cancelCheckout">Kembali</button><button class="button-primary" type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout">Konfirmasi & simpan</button></div>
            </section>
        </div>
    @endif
</div>
