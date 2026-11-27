<div class="custom-orders-page">
    @if (session('status'))<div class="notice-card"><strong>{{ session('status') }}</strong></div>@endif
    <div class="orders-layout">
        <section id="buat-pesanan" class="catalog-panel order-form-panel">
            <div class="catalog-panel-heading"><div><h2>Buat pesanan</h2><p>Catat barang dan jadwal pengambilan pelanggan.</p></div></div>
            <form class="catalog-form" wire:submit="createOrder">
                <div class="pos-input-grid"><label>Nama pelanggan<input wire:model="customerName" maxlength="160">@error('customerName')<small>{{ $message }}</small>@enderror</label><label>Nomor telepon (opsional)<input type="tel" wire:model="customerPhone" maxlength="40" placeholder="Boleh dikosongkan"></label></div>
                <div class="pos-input-grid"><label>Tanggal pesanan<input type="date" wire:model="orderDate">@error('orderDate')<small>{{ $message }}</small>@enderror</label><label>Jadwal pengambilan<input type="date" wire:model="estimatedCompletionDate">@error('estimatedCompletionDate')<small>{{ $message }}</small>@enderror</label></div>
                <label>Barang<select wire:model.live="productId"><option value="">Pilih barang</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select>@error('productId')<small>{{ $message }}</small>@enderror</label>
                @if($selectedProduct)
                    @php
                        $usesDiameterPrices = collect($dimensionSets)->contains(fn ($set) => filled($set['diameter'] ?? null));
                        $hasSizeOptions = collect($dimensionSets)->contains(fn ($set) => filled($set['shape'] ?? null) || filled($set['length'] ?? null) || filled($set['width'] ?? null));
                        $selectedOrderSpecifications = [];
                        foreach ($selectedProduct->category->specifications as $specification) {
                            $selection = $specificationInputs[$specification->key] ?? '';
                            $value = $selection === '__custom__' ? ($customSpecificationInputs[$specification->key] ?? '') : $selection;
                            if (filled($value)) {
                                $selectedOrderSpecifications[$specification->name] = $value;
                            }
                        }
                        $estimatedAmount = $priceBasis === 'weight' ? (float) ($estimatedWeight ?: 0) : (float) ($quantity ?: 0);
                        $estimatedTotal = $estimatedAmount * (float) ($agreedPrice ?: 0);
                    @endphp
                    <div class="pos-selected-product">
                        <img src="{{ $selectedProduct->image_path ? asset('storage/'.$selectedProduct->image_path) : asset('images/product-placeholder-steel.svg') }}" alt="Foto {{ $selectedProduct->name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder-steel.svg') }}'">
                        <div><strong>{{ $selectedProduct->name }}</strong><small>{{ $selectedProduct->sku }} · {{ $selectedProduct->category->name }}</small>@if($selectedProduct->description)<small>{{ $selectedProduct->description }}</small>@endif<small>Ukuran dan harga mengikuti data kategori barang.</small></div>
                    </div>
                    @if($selectedProduct->category->specifications->isNotEmpty())
                        <section class="pos-size-picker" aria-labelledby="order-size-picker-title">
                            <div class="pos-size-picker-heading"><strong id="order-size-picker-title">Pilih ukuran atau spesifikasi barang</strong><small>Daftar dari kategori {{ $selectedProduct->category->name }}</small></div>
                            @if($dimensionSets !== [])
                                @if($hasSizeOptions)
                                    <div class="pos-spec-option-group"><span class="pos-spec-option-label">Ukuran</span><div class="pos-spec-options" role="group" aria-label="Pilihan ukuran barang">
                                        @foreach($dimensionSets as $optionIndex => $dimensionSet)
                                            @if(filled($dimensionSet['shape'] ?? null) || filled($dimensionSet['length'] ?? null) || filled($dimensionSet['width'] ?? null))
                                                @php($dimensionLabel = collect([filled($dimensionSet['shape'] ?? '') ? $dimensionSet['shape'] : null, filled($dimensionSet['length'] ?? '') ? 'P '.$dimensionSet['length'] : null, filled($dimensionSet['width'] ?? '') ? 'L '.$dimensionSet['width'] : null])->filter()->implode(', '))
                                                <button type="button" wire:key="order-size-{{ $optionIndex }}" class="pos-spec-option {{ $sizePresetId === 'dimension:'.$optionIndex ? 'active' : '' }}" wire:click="selectSizePreset('dimension:{{ $optionIndex }}')">{{ $dimensionLabel }}</button>
                                            @endif
                                        @endforeach
                                        <button type="button" class="pos-spec-option {{ $sizePresetId === '__custom__' ? 'active' : '' }}" wire:click="selectSizePreset('__custom__')">Custom</button>
                                    </div></div>
                                @endif
                                @if($usesDiameterPrices)
                                    <div class="pos-spec-option-group"><span class="pos-spec-option-label">Diameter besi · harga per kg</span><div class="pos-spec-options" role="group" aria-label="Pilihan diameter besi">
                                        @php($listedDiameters = [])
                                        @foreach($dimensionSets as $diameterIndex => $dimensionSet)
                                            @if(filled($dimensionSet['diameter'] ?? null) && !in_array(mb_strtolower($dimensionSet['diameter']), $listedDiameters, true))
                                                @php($listedDiameters[] = mb_strtolower($dimensionSet['diameter']))
                                                <button type="button" wire:key="order-diameter-{{ $diameterIndex }}" class="pos-spec-option {{ ($specificationInputs['diameter_besi'] ?? '') === $dimensionSet['diameter'] ? 'active' : '' }}" wire:click="selectDiameterPreset('diameter:{{ $diameterIndex }}')">{{ $dimensionSet['diameter'] }} · @rupiah($dimensionSet['price'] ?? 0)/kg</button>
                                            @endif
                                        @endforeach
                                    </div>@error('diameterPresetId')<small>{{ $message }}</small>@enderror</div>
                                @endif
                            @endif
                            @foreach($selectedProduct->category->specifications as $specification)
                                @if($dimensionSets !== [] && in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true))
                                    @if($sizePresetId === '__custom__' && in_array($specification->key, ['bentuk', 'panjang', 'lebar'], true))
                                        <label>{{ $specification->name }}@if($specification->measurement_unit) ({{ $specification->measurement_unit }})@endif<input type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="customSpecificationInputs.{{ $specification->key }}" placeholder="Masukkan {{ strtolower($specification->name) }} custom"></label>
                                    @endif
                                    @continue
                                @endif
                                @php($specificationSelection = $specificationInputs[$specification->key] ?? '')
                                <div class="pos-spec-option-group" wire:key="order-specification-{{ $specification->id }}"><span class="pos-spec-option-label">{{ $specification->name }}@if($specification->measurement_unit) ({{ $specification->measurement_unit }})@endif @if($specification->is_required)<span aria-hidden="true">*</span>@endif</span>
                                    @if(filled($specification->options))
                                        <div class="pos-spec-options" role="group" aria-label="{{ $specification->name }}">
                                            @foreach($specification->options as $optionIndex => $option)
                                                <button type="button" wire:key="order-spec-option-{{ $specification->id }}-{{ $optionIndex }}" class="pos-spec-option {{ $specificationSelection === (string) $option ? 'active' : '' }}" wire:click="$set('specificationInputs.{{ $specification->key }}', @js((string) $option))">{{ $option }}</button>
                                            @endforeach
                                            @if($specification->allow_custom)<button type="button" class="pos-spec-option {{ $specificationSelection === '__custom__' ? 'active' : '' }}" wire:click="$set('specificationInputs.{{ $specification->key }}', '__custom__')">Custom</button>@endif
                                        </div>
                                    @elseif($specification->data_type === 'boolean')
                                        <select wire:model.live="specificationInputs.{{ $specification->key }}"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>
                                    @else
                                        <input type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="specificationInputs.{{ $specification->key }}" placeholder="Isi {{ strtolower($specification->name) }}">
                                    @endif
                                    @if($specificationSelection === '__custom__')<input type="{{ in_array($specification->data_type, ['number', 'integer'], true) ? 'number' : 'text' }}" @if($specification->data_type === 'number')step="any"@elseif($specification->data_type === 'integer')step="1"@endif wire:model.live.debounce.250ms="customSpecificationInputs.{{ $specification->key }}" placeholder="Masukkan {{ strtolower($specification->name) }} custom">@endif
                                    @error('specificationInputs.'.$specification->key)<small>{{ $message }}</small>@enderror
                                </div>
                            @endforeach
                        </section>
                    @endif
                    @error('sizePresetId')<small class="catalog-error">{{ $message }}</small>@enderror
                    <div class="pos-input-grid">
                        @if($priceBasis !== 'weight')<label>Jumlah {{ $selectedProduct->unit->abbreviation }}<input type="number" min="0.001" step="0.001" wire:model.live="quantity">@error('quantity')<small>{{ $message }}</small>@enderror</label>@else<label>Berat estimasi (kg)<input type="number" min="0.001" step="0.001" wire:model.live="estimatedWeight">@error('estimatedWeight')<small>{{ $message }}</small>@enderror</label>@endif
                        @if($priceBasis === 'weight' && $selectedProduct->pricing_method !== 'weight')<label>Jumlah {{ $selectedProduct->unit->abbreviation }}<input type="number" min="0.001" step="0.001" wire:model.live="quantity">@error('quantity')<small>{{ $message }}</small>@enderror</label>@endif
                    </div>
                    @if(! $usesDiameterPrices && $selectedProduct->pricing_method !== 'weight')
                        <div class="pos-input-grid"><label>Dasar harga<select wire:model.live="priceBasis"><option value="unit">Per satuan</option><option value="weight">Per kg</option><option value="fixed">Harga total tetap</option></select></label><label>{{ $priceBasis === 'weight' ? 'Harga per kg' : ($priceBasis === 'unit' ? 'Harga per satuan' : 'Total harga') }} (Rp)<input type="number" min="0" step="1" wire:model.live="agreedPrice">@error('agreedPrice')<small>{{ $message }}</small>@enderror</label></div>
                    @elseif(! $usesDiameterPrices)
                        <label>Harga per kg (Rp)<input type="number" min="0" step="1" wire:model.live="agreedPrice">@error('agreedPrice')<small>{{ $message }}</small>@enderror</label>
                    @else
                        <label>Harga per kg (Rp)<input type="number" min="0" step="1" wire:model.live="agreedPrice" readonly>@error('agreedPrice')<small>{{ $message }}</small>@enderror</label>
                    @endif
                    @if($selectedOrderSpecifications)<div class="pos-estimated-total"><span>Spesifikasi terpilih</span><strong>{{ collect($selectedOrderSpecifications)->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</strong></div>@endif
                    @if($estimatedAmount > 0 && (float) $agreedPrice > 0)<div class="pos-estimated-total"><span>Estimasi nilai pesanan</span><strong>@rupiah($estimatedTotal)</strong></div>@endif
                @endif
                @error('orderItems')<small class="catalog-error">{{ $message }}</small>@enderror
                @if($orderItems)
                    <div class="order-draft-list">
                        <strong>Barang pesanan</strong>
                        @foreach($orderItems as $item)
                            <div class="order-draft-item" wire:key="draft-{{ $item['key'] }}">
                                <span>
                                    {{ $item['product_name'] }} · @quantity($item['quantity']) {{ $item['stock_unit'] }}
                                    @if($item['estimated_weight']) · @quantity($item['estimated_weight']) kg @endif
                                    <small>
                                        @if($item['price_basis'] === 'weight')
                                            @rupiah($item['agreed_price'])/kg ·
                                        @endif
                                        Nilai @rupiah($item['total_price'])
                                    </small>
                                    @if($item['specifications'])
                                        <small>{{ collect($item['specifications'])->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</small>
                                    @endif
                                </span>
                                <button type="button" class="text-button text-danger" wire:click="removeOrderItem('{{ $item['key'] }}')">Hapus</button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <button class="button-primary" type="submit" wire:loading.attr="disabled" wire:target="createOrder">Simpan pesanan</button>
            </form>
        </section>
        <section class="catalog-panel order-list-panel">
            <div class="catalog-panel-heading"><div><h2>Daftar pesanan</h2><p>Pantau produksi, stok barang jadi, dan pengambilan.</p></div></div>
            <div class="pos-input-grid"><input class="catalog-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor atau pelanggan"><select class="catalog-input" wire:model.live="statusFilter"><option value="">Semua status</option>@foreach(['pending'=>'Menunggu','producing'=>'Diproduksi','made'=>'Selesai dibuat','ready'=>'Siap diambil','delivered'=>'Selesai','canceled'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="orders-list">
                @forelse($orders as $order)
                    <article class="order-card" wire:key="order-{{ $order->id }}">
                        <div class="order-card-main"><div><div class="order-heading"><strong>{{ $order->order_number }}</strong><span class="catalog-status">{{ ['pending'=>'Menunggu','producing'=>'Diproduksi','made'=>'Selesai dibuat','ready'=>'Siap diambil','delivered'=>'Selesai','canceled'=>'Dibatalkan'][$order->status] }}</span></div><p>{{ $order->customer_name }} @if($order->customer_phone)· {{ $order->customer_phone }}@endif</p><small>{{ $order->items->count() }} jenis barang · {{ $order->order_date->format('d/m/Y') }} · Pengambilan {{ $order->estimated_completion_date?->format('d/m/Y') ?? 'belum dijadwalkan' }}</small></div><div class="order-money"><strong>@rupiah($order->total_price)</strong><small>Nilai pesanan</small></div></div>
                        @error('orderStatus')<small class="catalog-error">{{ $message }}</small>@enderror
                        <button class="button-secondary button-small" type="button" wire:click="showDetails({{ $order->id }})">{{ $expandedOrderId === $order->id ? 'Perbarui detail' : 'Detail pesanan' }}</button>
                        @if($expandedOrderId === $order->id)
                            <div class="order-details">
                                @foreach($order->items as $item)
                                    <div class="order-draft-item"><span><strong>{{ $item->product_name }}</strong><small>@quantity($item->quantity) {{ $item->stock_unit }} · {{ $item->price_basis === 'weight' ? 'harga/kg' : ($item->price_basis === 'unit' ? 'harga/satuan' : 'harga tetap') }}</small>@if($item->diameter || $item->shape || $item->specifications)<small>{{ $item->diameter }} {{ $item->shape }} {{ collect($item->specifications ?? [])->map(fn($v,$k)=>$k.': '.$v)->implode(' · ') }}</small>@endif</span><strong>@rupiah($item->total_price)</strong></div>
                                    @if(in_array($order->status, ['producing','made','ready'], true))<div class="pos-input-grid order-item-edit"><label>Berat aktual (kg)<input type="number" min="0.001" step="0.001" wire:model="itemChanges.{{ $item->id }}.actual_weight">@error('itemChanges.'.$item->id.'.actual_weight')<small>{{ $message }}</small>@enderror</label><label>{{ $item->price_basis === 'weight' ? 'Harga per kg' : ($item->price_basis === 'unit' ? 'Harga per satuan' : 'Harga total') }}<input type="number" min="0.01" step="1" wire:model="itemChanges.{{ $item->id }}.agreed_price">@error('itemChanges.'.$item->id.'.agreed_price')<small>{{ $message }}</small>@enderror</label></div>@endif
                                @endforeach
                                @if($order->status === 'pending')<button class="button-primary button-small" wire:click="transitionOrderStatus({{ $order->id }}, 'producing')">Mulai produksi</button>@elseif($order->status === 'producing')<button class="button-primary button-small" wire:click="transitionOrderStatus({{ $order->id }}, 'made')">Simpan detail & tandai selesai dibuat</button>@elseif($order->status === 'made')<button class="button-primary button-small" wire:click="transitionOrderStatus({{ $order->id }}, 'ready')">Tandai siap diambil</button>@elseif($order->status === 'ready')<button class="button-primary button-small" wire:click="preparePayment({{ $order->id }})">Terima pembayaran tunai</button>@endif
                                @if(in_array($order->status,['pending','producing','made'],true))<div class="cancel-order"><label>Alasan pembatalan<input wire:model="cancellationReason"></label><button class="button-danger button-small" wire:click="transitionOrderStatus({{ $order->id }}, 'canceled')" wire:confirm="Batalkan pesanan ini?">Batalkan</button>@error('cancellationReason')<small>{{ $message }}</small>@enderror</div>@endif
                                @if($order->stockMovements->isNotEmpty())<div class="order-timeline"><strong>Pergerakan stok pesanan</strong>@foreach($order->stockMovements as $movement)<div class="timeline-row"><span>{{ $movement->movement_type === 'custom_order_ready' ? 'Barang jadi masuk' : 'Barang diambil' }} · @quantity(abs($movement->quantity)) {{ $movement->product?->unit?->abbreviation ?? '' }}</span><small>{{ $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</small></div>@endforeach</div>@endif
                            </div>
                        @endif
                    </article>
                @empty<p class="empty-state">Belum ada pesanan.</p>@endforelse
            </div>{{ $orders->links() }}
        </section>
    </div>
    @if($paymentOrderId)
        @php($paymentOrder = $orders->getCollection()->firstWhere('id', $paymentOrderId))
        @if($paymentOrder)@php($paymentTotal = $paymentOrder->items->sum(function ($item) use ($itemChanges) { $values = $itemChanges[$item->id] ?? []; $weight = filled($values['actual_weight'] ?? null) ? (float) $values['actual_weight'] : (float) ($item->actual_weight ?? 0); $price = filled($values['agreed_price'] ?? null) ? (float) $values['agreed_price'] : (float) ($item->agreed_price ?? 0); return match ($item->price_basis) { 'weight' => $weight * $price, 'unit' => (float) $item->quantity * $price, default => $price }; }))<div class="catalog-modal-backdrop"><section class="catalog-modal checkout-modal" role="dialog" aria-modal="true" aria-labelledby="order-payment-title"><div class="catalog-panel-heading"><div><h2 id="order-payment-title">Pembayaran tunai</h2><p>{{ $paymentOrder->order_number }} · {{ $paymentOrder->customer_name }}</p></div><button class="button-secondary button-small" wire:click="closePayment">Tutup</button></div><div class="confirmation-summary">@foreach($paymentOrder->items as $item)<div><span>{{ $item->product_name }}</span><strong>@rupiah($item->total_price)</strong></div>@endforeach</div><div class="confirmation-total"><span>Total akhir</span><strong>@rupiah($paymentTotal)</strong></div><label class="cash-input-label">Uang tunai diterima<input class="catalog-input" type="number" min="0" step="1" wire:model.live="amountReceived">@error('amountReceived')<small>{{ $message }}</small>@enderror</label><div class="change-summary"><span>Kembalian</span><strong>@rupiah(max(0, (float) $amountReceived - $paymentTotal))</strong></div><div class="modal-actions"><button class="button-primary" wire:click="confirmPayment" wire:loading.attr="disabled">Simpan pembayaran</button></div></section></div>@endif
    @endif
</div>
