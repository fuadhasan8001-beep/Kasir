<section class="catalog-panel pos-checkout-panel {{ $isCartPage ? 'cart-page-panel' : '' }}">
    <div class="catalog-panel-heading">
        <div class="mini-cart-title"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><h2>{{ $isCartPage ? 'Keranjang transaksi' : 'Keranjang' }}</h2></div>
        <span class="cart-badge">{{ count($cart) }}</span>
    </div>

    <label class="customer-field">Nama pembeli <span>(opsional)</span><input class="catalog-input" type="text" wire:model.live.debounce.250ms="customerName" maxlength="160" placeholder="Nama pembeli"></label>

    <div class="cart-section">
        <div class="cart-heading"><h3>Barang dipilih</h3>@if($cart)<button class="text-button" type="button" wire:click="clearCart" wire:confirm="Kosongkan semua barang dalam keranjang?">Kosongkan</button>@endif</div>
        <div class="cart-list">
            @forelse ($cartForDisplay as $item)
                <article class="cart-item" wire:key="cart-{{ $item['key'] }}">
                    <img class="cart-product-image" src="{{ !empty($item['image_path']) ? asset('storage/'.$item['image_path']) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $item['product_name'] }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">
                    <div class="cart-item-main">
                        <strong>{{ $item['product_name'] }}</strong>
                        <small>@if($item['pricing_basis'] === 'weight'){{ number_format($item['actual_weight'], 3, ',', '.') }} kg @else{{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit_name'] }}@endif</small>
                        @if($item['price_available'])<small>@rupiah($item['unit_price']) / {{ $item['pricing_basis'] === 'weight' ? 'kg' : $item['stock_unit'] }}</small>@else<small class="catalog-error">Harga variasi tidak tersedia</small>@endif
                        @if($item['specifications'])<small>{{ collect($item['specifications'])->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>@endif
                        @if($item['notes'])<small>{{ $item['notes'] }}</small>@endif
                        <div class="cart-item-actions"><button class="text-button" type="button" wire:click="editItem('{{ $item['key'] }}')">Ubah</button><button class="text-button text-danger" type="button" wire:click="removeItem('{{ $item['key'] }}')">Hapus</button></div>
                    </div>
                    <b class="cart-item-total">@if($item['price_available'])@rupiah($item['line_total'])@else—@endif</b>
                </article>
            @empty
                <div class="mini-cart-empty"><svg aria-hidden="true" viewBox="0 0 64 64" fill="none"><path d="M9 12h6l5.8 29.2a5.2 5.2 0 0 0 5.1 4.2h22.4a5 5 0 0 0 4.8-3.6L59 22H17"/><circle cx="27" cy="54" r="3.5"/><circle cx="47" cy="54" r="3.5"/><path d="M27 29h14m-7-7v14"/></svg><strong>Keranjang masih kosong</strong><small>Pilih produk dari katalog untuk mulai bertransaksi.</small><a class="button-secondary button-small" href="{{ route('kasir') }}">Kembali ke katalog</a></div>
            @endforelse
            @error('cart')<small class="catalog-error">{{ $message }}</small>@enderror
        </div>
    </div>

    <div class="checkout-summary">
        <div><span>Subtotal</span><strong>@rupiah($subtotal)</strong></div>
        <div class="grand-total"><span>Total pembayaran</span><strong>@rupiah($subtotal)</strong></div>
        <button class="button-primary checkout-button" type="button" wire:click="startCheckout" @disabled(!$cart)>Bayar / Proses Transaksi</button>
    </div>
</section>
