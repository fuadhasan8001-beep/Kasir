<div class="pos-page">
    @if (session('status')) <div class="notice-card"><div><strong>{{ session('status') }}</strong></div></div> @endif
    @if ($completedInvoice)
        <div class="receipt-ready"><div><strong>Catatan tersimpan</strong><p>Nomor catatan {{ $completedInvoice }}</p></div></div>
    @endif
    <div class="pos-layout">
        <section class="catalog-panel pos-products">
            <div class="catalog-panel-heading"><div><h2>Pilih barang</h2><p>Cari nama atau kode barang dari katalog.</p></div></div>
            <label class="search-label"><span>Pencarian barang</span><input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Contoh: besi 10 mm"></label>
            <div class="pos-product-list">
                @forelse ($products as $product)
                    <button class="pos-product" type="button" wire:key="product-{{ $product->id }}" wire:click="$set('productId', {{ $product->id }})">
                        <span><strong>{{ $product->name }}</strong><small>{{ $product->sku }} · {{ $product->unit->abbreviation }} · {{ $product->pricing_method === 'weight' ? 'Harga per kg' : 'Harga per '.$product->unit->abbreviation }}</small></span>
                        <b>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</b>
                    </button>
                @empty <p class="empty-state">Barang tidak ditemukan atau belum ada barang aktif.</p> @endforelse
            </div>
        </section>
        <section class="catalog-panel pos-checkout">
            <div class="catalog-panel-heading"><div><h2>Detail pesanan</h2><p>Masukkan ukuran, jumlah, dan hasil timbangan.</p></div></div>
            <form class="catalog-form" wire:submit="addItem">
                <label>Barang dipilih
                    <select wire:model="productId"><option value="">Pilih barang</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>@endforeach</select>
                    @error('productId') <small>{{ $message }}</small> @enderror
                </label>
                <div class="pos-input-grid">
                    <label>Jumlah pesanan<input type="number" min="0.001" step="0.001" wire:model="quantity" placeholder="1">@error('quantity')<small>{{ $message }}</small>@enderror</label>
                    <label>Berat aktual (kg)<input type="number" min="0.001" step="0.001" wire:model="actualWeight" placeholder="Wajib untuk harga/kg">@error('actualWeight')<small>{{ $message }}</small>@enderror</label>
                </div>
                <div class="pos-input-grid">
                    <label>Diameter<input type="text" wire:model="diameter" placeholder="Mis. 10 mm"></label>
                    <label>Panjang<input type="text" wire:model="length" placeholder="Mis. 4 m"></label>
                    <label>Lebar<input type="text" wire:model="width" placeholder="Mis. 20 cm"></label>
                </div>
                <label>Catatan pesanan<textarea rows="2" wire:model="orderNotes" placeholder="Ukuran custom atau instruksi khusus"></textarea></label>
                <button class="button-secondary" type="submit">Tambah ke transaksi</button>
            </form>
            <div class="cart-list">
                @forelse ($cart as $item)
                    <div class="cart-item" wire:key="cart-{{ $item['key'] }}"><div><strong>{{ $item['product_name'] }}</strong><small>{{ $item['quantity'] }} {{ $item['unit_name'] }}@if($item['actual_weight']) · timbang {{ $item['actual_weight'] }} kg @endif @if($item['specifications']) · {{ implode(', ', array_map(fn ($key, $value) => $key.': '.$value, array_keys($item['specifications']), $item['specifications'])) }} @endif</small>@if($item['notes'])<small>{{ $item['notes'] }}</small>@endif</div><div><b>Rp {{ number_format($item['line_total'], 0, ',', '.') }}</b><button class="text-button" type="button" wire:click="removeItem('{{ $item['key'] }}')">Hapus</button></div></div>
                @empty <div class="empty-state">Belum ada barang dalam transaksi.</div> @endforelse
                @error('cart') <small class="catalog-error">{{ $message }}</small> @enderror
            </div>
            <div class="checkout-summary">
                <div><span>Nilai catatan</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                <button class="button-primary checkout-button" type="button" wire:click="saveNote" wire:loading.attr="disabled">Simpan Catatan</button>
            </div>
        </section>
    </div>
</div>
