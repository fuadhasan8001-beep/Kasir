<?php

namespace App\Livewire\Orders;

use App\Models\CustomOrder;
use App\Models\CustomOrderItem;
use App\Models\CustomOrderPayment;
use App\Models\CustomOrderStatusHistory;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ManageCustomOrders extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public string $orderDate = '';

    public ?int $productId = null;

    public string $specifications = '';

    public string $diameter = '';

    public string $shape = '';

    public string $quantity = '1';

    public string $estimatedWeight = '';

    public string $priceBasis = 'unit';

    public string $agreedPrice = '';

    public string $estimatedCompletionDate = '';

    public string $notes = '';

    /** @var array<int, array<string, mixed>> */
    public array $orderItems = [];

    /** @var array<int, array{actual_weight: string, agreed_price: string}> */
    public array $itemChanges = [];

    public string $createToken = '';

    public string $paymentToken = '';

    public string $amountReceived = '';

    public string $cancellationReason = '';

    public ?int $expandedOrderId = null;

    public ?int $paymentOrderId = null;

    public function mount(): void
    {
        $this->orderDate = now()->timezone('Asia/Jakarta')->toDateString();
        $this->createToken = (string) Str::uuid();
        $this->paymentToken = (string) Str::uuid();
    }

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->validateOnly('statusFilter', ['statusFilter' => ['nullable', Rule::in(['pending', 'producing', 'made', 'ready', 'delivered', 'canceled'])]]);
        $this->resetPage();
    }

    public function addOrderItem(): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'estimatedWeight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'priceBasis' => ['required', Rule::in(['weight', 'unit', 'fixed'])],
            'agreedPrice' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'specifications' => ['nullable', 'string', 'max:1000'],
            'diameter' => ['nullable', 'string', 'max:80'],
            'shape' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = Product::query()->with('unit')->where('is_active', true)->findOrFail($validated['productId']);
        $weight = filled($validated['estimatedWeight']) ? (float) $validated['estimatedWeight'] : null;
        $price = filled($validated['agreedPrice']) ? (float) $validated['agreedPrice'] : null;

        if ($validated['priceBasis'] === 'weight' && $price !== null && $weight === null) {
            $this->addError('estimatedWeight', 'Masukkan berat estimasi untuk menghitung nilai pesanan per kilogram.');

            return;
        }

        $lineTotal = match ($validated['priceBasis']) {
            'weight' => $weight !== null && $price !== null ? round($weight * $price, 2) : 0,
            'unit' => $price !== null ? round((float) $validated['quantity'] * $price, 2) : 0,
            default => $price ?? 0,
        };

        $this->orderItems[] = [
            'key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => (float) $validated['quantity'],
            'stock_basis' => $product->pricing_method === 'weight' ? 'weight' : 'unit',
            'stock_unit' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
            'estimated_weight' => $weight,
            'actual_weight' => null,
            'price_basis' => $validated['priceBasis'],
            'agreed_price' => $price,
            'total_price' => $lineTotal,
            'specifications' => filled($validated['specifications']) ? ['Rincian' => trim($validated['specifications'])] : [],
            'diameter' => $validated['diameter'] ?: null,
            'shape' => $validated['shape'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        $this->reset(['productId', 'specifications', 'diameter', 'shape', 'estimatedWeight', 'agreedPrice', 'notes']);
        $this->quantity = '1';
        $this->priceBasis = 'unit';
        $this->resetValidation();
    }

    public function removeOrderItem(string $key): void
    {
        $this->orderItems = array_values(array_filter($this->orderItems, fn (array $item): bool => $item['key'] !== $key));
    }

    public function createOrder(): void
    {
        if ($this->orderItems === []) {
            $this->addError('orderItems', 'Tambahkan minimal satu barang ke pesanan.');

            return;
        }

        $validated = $this->validate([
            'customerName' => ['required', 'string', 'max:160'],
            'customerPhone' => ['nullable', 'string', 'max:40'],
            'orderDate' => ['required', 'date', 'before_or_equal:today'],
            'estimatedCompletionDate' => ['nullable', 'date', 'after_or_equal:orderDate'],
            'orderItems' => ['required', 'array', 'min:1', 'max:50'],
            'orderItems.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'orderItems.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'orderItems.*.stock_basis' => ['required', Rule::in(['unit', 'weight'])],
            'orderItems.*.stock_unit' => ['required', 'string', 'max:32'],
            'orderItems.*.estimated_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'orderItems.*.price_basis' => ['required', Rule::in(['weight', 'unit', 'fixed'])],
            'orderItems.*.agreed_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'orderItems.*.specifications' => ['array', 'max:10'],
            'orderItems.*.specifications.*' => ['string', 'max:1000'],
            'orderItems.*.diameter' => ['nullable', 'string', 'max:80'],
            'orderItems.*.shape' => ['nullable', 'string', 'max:100'],
            'orderItems.*.notes' => ['nullable', 'string', 'max:2000'],
            'createToken' => ['required', 'uuid'],
        ], [
            'customerName.required' => 'Nama pelanggan wajib diisi.',
            'orderDate.before_or_equal' => 'Tanggal pemesanan tidak boleh di masa depan.',
            'estimatedCompletionDate.after_or_equal' => 'Jadwal pengambilan tidak boleh sebelum tanggal pemesanan.',
        ]);

        $order = DB::transaction(function () use ($validated): CustomOrder {
            $existingOrder = CustomOrder::query()->where('request_key', $validated['createToken'])->first();

            if ($existingOrder !== null) {
                return $existingOrder;
            }

            $normalizedItems = [];
            $total = 0.0;

            foreach ($validated['orderItems'] as $item) {
                $product = Product::query()->with('unit')->where('is_active', true)->lockForUpdate()->findOrFail($item['product_id']);
                $weight = filled($item['estimated_weight']) ? (float) $item['estimated_weight'] : null;
                $price = filled($item['agreed_price']) ? (float) $item['agreed_price'] : null;
                $itemTotal = match ($item['price_basis']) {
                    'weight' => $weight !== null && $price !== null ? round($weight * $price, 2) : 0,
                    'unit' => $price !== null ? round((float) $item['quantity'] * $price, 2) : 0,
                    default => $price ?? 0,
                };

                $normalizedItems[] = [
                    'product' => $product,
                    'quantity' => (float) $item['quantity'],
                    'stock_basis' => $product->pricing_method === 'weight' ? 'weight' : 'unit',
                    'stock_unit' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
                    'estimated_weight' => $weight,
                    'price_basis' => $item['price_basis'],
                    'agreed_price' => $price,
                    'total_price' => $itemTotal,
                    'specifications' => $item['specifications'] ?: null,
                    'diameter' => $item['diameter'] ?: null,
                    'shape' => $item['shape'] ?: null,
                    'notes' => $item['notes'] ?: null,
                ];
                $total += $itemTotal;
            }

            $firstItem = $normalizedItems[0];
            $order = CustomOrder::create([
                'order_number' => 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'request_key' => $validated['createToken'],
                'created_by' => auth()->id(),
                'product_id' => $firstItem['product']->id,
                'product_name' => $firstItem['product']->name,
                'customer_name' => trim($validated['customerName']),
                'customer_phone' => $validated['customerPhone'] ?: null,
                'order_date' => $validated['orderDate'],
                'specifications' => $firstItem['specifications'],
                'diameter' => $firstItem['diameter'],
                'shape' => $firstItem['shape'],
                'quantity' => $firstItem['quantity'],
                'stock_basis' => $firstItem['stock_basis'],
                'stock_unit' => $firstItem['stock_unit'],
                'estimated_weight' => $firstItem['estimated_weight'],
                'price_basis' => $firstItem['price_basis'],
                'agreed_price' => $firstItem['agreed_price'] ?? 0,
                'total_price' => round($total, 2),
                'estimated_completion_date' => $validated['estimatedCompletionDate'] ?: null,
                'notes' => $validated['orderItems'][0]['notes'] ?: null,
                'status' => 'pending',
            ]);

            foreach ($normalizedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'stock_basis' => $item['stock_basis'],
                    'stock_unit' => $item['stock_unit'],
                    'estimated_weight' => $item['estimated_weight'],
                    'price_basis' => $item['price_basis'],
                    'agreed_price' => $item['agreed_price'],
                    'total_price' => $item['total_price'],
                    'specifications' => $item['specifications'],
                    'diameter' => $item['diameter'],
                    'shape' => $item['shape'],
                    'notes' => $item['notes'],
                ]);
            }

            $this->recordStatus($order, null, 'pending');

            return $order;
        });

        $this->resetForm();
        session()->flash('status', 'Pesanan '.$order->order_number.' berhasil dicatat.');
    }

    public function saveActualWeight(int $orderId): void
    {
        $this->saveOrderItemDetails($orderId, 'made');
        session()->flash('status', 'Detail jumlah, berat, dan harga pesanan diperbarui.');
    }

    public function preparePayment(int $orderId): void
    {
        $order = CustomOrder::query()->with('items')->findOrFail($orderId);

        if ($order->status !== 'ready') {
            throw ValidationException::withMessages(['orderStatus' => 'Pembayaran hanya dapat diproses untuk pesanan yang siap diambil.']);
        }

        $this->expandedOrderId = $order->id;
        $this->paymentOrderId = $order->id;
        $this->amountReceived = '';
        $this->paymentToken = (string) Str::uuid();
        $this->loadItemChanges($order);
        $this->resetValidation();
    }

    public function confirmPayment(): void
    {
        if ($this->paymentOrderId === null) {
            return;
        }

        $validated = $this->validate([
            'amountReceived' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'paymentToken' => ['required', 'uuid'],
            'itemChanges' => ['required', 'array', 'min:1', 'max:50'],
            'itemChanges.*.actual_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'itemChanges.*.agreed_price' => ['nullable', 'numeric', 'gt:0', 'max:9999999999999.99'],
        ]);

        $result = DB::transaction(function () use ($validated): Sale {
            $existingSale = Sale::query()->where('request_key', $validated['paymentToken'])->first();

            if ($existingSale !== null) {
                return $existingSale->load('items');
            }

            $order = CustomOrder::query()->with('items')->lockForUpdate()->findOrFail($this->paymentOrderId);

            if ($order->status !== 'ready') {
                throw ValidationException::withMessages(['orderStatus' => 'Pesanan ini sudah diproses atau belum siap diambil.']);
            }

            $total = 0.0;
            $saleItems = [];

            foreach ($order->items as $item) {
                $changes = $validated['itemChanges'][$item->id] ?? [];
                $actualWeight = filled($changes['actual_weight'] ?? null)
                    ? (float) $changes['actual_weight']
                    : ($item->actual_weight !== null ? (float) $item->actual_weight : null);
                $price = filled($changes['agreed_price'] ?? null)
                    ? (float) $changes['agreed_price']
                    : ($item->agreed_price !== null ? (float) $item->agreed_price : null);

                if ($item->stock_basis === 'weight' && ($actualWeight === null || $actualWeight <= 0)) {
                    throw ValidationException::withMessages(['itemChanges.'.$item->id.'.actual_weight' => 'Berat aktual barang wajib diisi.']);
                }

                if ($price === null || $price <= 0) {
                    throw ValidationException::withMessages(['itemChanges.'.$item->id.'.agreed_price' => 'Harga akhir barang wajib diisi.']);
                }

                $lineTotal = match ($item->price_basis) {
                    'weight' => round((float) $actualWeight * $price, 2),
                    'unit' => round((float) $item->quantity * $price, 2),
                    default => round($price, 2),
                };

                $previousActualWeight = $item->actual_weight !== null ? (float) $item->actual_weight : null;
                $item->actual_weight = $actualWeight;
                $item->agreed_price = $price;
                $item->total_price = $lineTotal;
                $item->save();

                $total += $lineTotal;
                $saleItems[] = [$item, $lineTotal, $previousActualWeight];
            }

            $total = round($total, 2);

            if ((float) $validated['amountReceived'] < $total) {
                throw ValidationException::withMessages(['amountReceived' => 'Uang tunai belum mencukupi total tagihan.']);
            }

            $sale = Sale::create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'request_key' => $validated['paymentToken'],
                'user_id' => auth()->id(),
                'customer_name' => $order->customer_name,
                'custom_order_id' => $order->id,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'amount_received' => $validated['amountReceived'],
                'change_amount' => round((float) $validated['amountReceived'] - $total, 2),
                'payment_status' => 'paid',
            ]);

            foreach ($saleItems as [$item, $lineTotal, $previousActualWeight]) {
                $product = $item->product_id ? Product::withTrashed()->lockForUpdate()->find($item->product_id) : null;

                if ($product === null) {
                    throw ValidationException::withMessages(['orderStatus' => 'Barang '.$item->product_name.' tidak ditemukan untuk diproses.']);
                }

                $stockQuantity = $item->stock_basis === 'weight'
                    ? (float) $item->actual_weight
                    : (float) $item->quantity;
                $stock = InventoryLevel::query()
                    ->where('product_id', $product->id)
                    ->where('inventory_type', 'finished')
                    ->lockForUpdate()
                    ->first();

                if ($stock === null) {
                    throw ValidationException::withMessages(['orderStatus' => 'Stok barang '.$item->product_name.' tidak ditemukan.']);
                }

                if ($item->stock_basis === 'weight') {
                    $previousReadyWeight = (float) ($previousActualWeight ?? 0);
                    $weightDifference = round($stockQuantity - $previousReadyWeight, 3);

                    if ($weightDifference !== 0.0) {
                        $adjustedBalance = (float) $stock->quantity + $weightDifference;

                        if ($adjustedBalance < 0) {
                            throw ValidationException::withMessages(['orderStatus' => 'Berat aktual '.$item->product_name.' melebihi stok yang tersedia.']);
                        }

                        $stock->update(['quantity' => $adjustedBalance]);
                        StockMovement::create([
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'custom_order_id' => $order->id,
                            'custom_order_item_id' => $item->id,
                            'movement_type' => 'custom_order_weight_adjustment',
                            'quantity' => $weightDifference,
                            'balance_after' => $adjustedBalance,
                            'inventory_type' => 'finished',
                            'reference' => $order->order_number,
                        ]);
                        $stock->refresh();
                    }
                }

                if ((float) $stock->quantity < $stockQuantity) {
                    throw ValidationException::withMessages(['orderStatus' => 'Stok barang '.$item->product_name.' tidak mencukupi untuk penyerahan.']);
                }

                $stock->decrement('quantity', $stockQuantity);
                $balance = (float) $stock->fresh()->quantity;
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $item->product_name,
                    'unit_name' => $item->price_basis === 'weight' ? 'kg' : $item->stock_unit,
                    'pricing_basis' => $item->price_basis === 'fixed' ? 'custom' : $item->price_basis,
                    'quantity' => $item->quantity,
                    'actual_weight' => $item->actual_weight,
                    'specifications' => array_filter(array_merge($item->specifications ?? [], [
                        'Diameter' => $item->diameter,
                        'Bentuk' => $item->shape,
                    ])),
                    'notes' => $item->notes,
                    'unit_price' => $item->agreed_price,
                    'unit_cost' => $product->purchase_price,
                    'line_total' => $lineTotal,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'sale_id' => $sale->id,
                    'custom_order_id' => $order->id,
                    'custom_order_item_id' => $item->id,
                    'movement_type' => 'custom_order_delivery',
                    'quantity' => -1 * $stockQuantity,
                    'balance_after' => $balance,
                    'inventory_type' => 'finished',
                    'reference' => $sale->invoice_number,
                ]);
            }

            $firstItem = $order->items->first();
            $order->fill([
                'status' => 'delivered',
                'actual_weight' => $firstItem?->actual_weight,
                'agreed_price' => $firstItem?->agreed_price ?? 0,
                'total_price' => $total,
            ])->save();
            $this->recordStatus($order, 'ready', 'delivered', 'Pembayaran tunai diterima. Transaksi '.$sale->invoice_number.'.');

            CustomOrderPayment::create([
                'custom_order_id' => $order->id,
                'user_id' => auth()->id(),
                'request_key' => $validated['paymentToken'],
                'amount' => $total,
                'amount_received' => $validated['amountReceived'],
                'change_amount' => round((float) $validated['amountReceived'] - $total, 2),
                'balance_before' => $total,
                'balance_after' => 0,
                'payment_method' => 'cash',
                'paid_at' => now(),
            ]);

            return $sale->load('items');
        });

        $this->paymentOrderId = null;
        $this->amountReceived = '';
        $this->paymentToken = (string) Str::uuid();
        $this->itemChanges = [];
        session()->flash('status', 'Pesanan selesai dibayar tunai. Transaksi '.$result->invoice_number.' tersimpan di riwayat penjualan.');
    }

    public function closePayment(): void
    {
        $this->paymentOrderId = null;
        $this->amountReceived = '';
        $this->itemChanges = [];
        $this->resetValidation();
    }

    public function transitionOrderStatus(int $orderId, string $toStatus): void
    {
        abort_unless(in_array($toStatus, ['producing', 'made', 'ready', 'canceled'], true), 422);

        DB::transaction(function () use ($orderId, $toStatus): void {
            $order = CustomOrder::query()->with('items')->lockForUpdate()->findOrFail($orderId);
            $allowedTransitions = [
                'pending' => ['producing', 'canceled'],
                'producing' => ['made', 'canceled'],
                'made' => ['ready', 'canceled'],
            ];

            if (! in_array($toStatus, $allowedTransitions[$order->status] ?? [], true)) {
                throw ValidationException::withMessages(['orderStatus' => 'Perubahan status pesanan ini tidak diizinkan.']);
            }

            if ($toStatus === 'canceled' && blank($this->cancellationReason)) {
                throw ValidationException::withMessages(['cancellationReason' => 'Alasan pembatalan wajib diisi.']);
            }

            if ($toStatus === 'canceled' && mb_strlen(trim($this->cancellationReason)) > 1000) {
                throw ValidationException::withMessages(['cancellationReason' => 'Alasan pembatalan maksimal 1.000 karakter.']);
            }

            if ($toStatus === 'made') {
                $this->saveItemChangesOnOrder($order);
            }

            if ($toStatus === 'ready') {
                $this->saveItemChangesOnOrder($order);
            }

            if ($toStatus === 'ready') {
                $this->applyStockMovement($order, 'ready');
            }

            $fromStatus = $order->status;
            $order->status = $toStatus;
            $order->save();
            $reason = $toStatus === 'canceled' ? trim($this->cancellationReason) : null;
            $this->recordStatus($order, $fromStatus, $toStatus, $reason);
        });

        if ($toStatus === 'canceled') {
            $this->cancellationReason = '';
        }

        session()->flash('status', 'Status pesanan berhasil diperbarui.');
    }

    public function toggleDetails(int $orderId): void
    {
        $this->expandedOrderId = $this->expandedOrderId === $orderId ? null : $orderId;
        $this->paymentOrderId = null;
        $this->reset('cancellationReason');
        $this->resetValidation();
    }

    public function showDetails(int $orderId): void
    {
        $this->expandedOrderId = $orderId;
        $order = CustomOrder::query()->with('items')->findOrFail($orderId);
        $this->loadItemChanges($order);
        $this->paymentOrderId = null;
        $this->reset('cancellationReason');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.orders.manage-custom-orders', [
            'products' => Product::query()->with('unit')->where('is_active', true)->orderBy('name')->get(),
            'orders' => CustomOrder::query()
                ->with(['creator', 'items', 'statusHistories.user', 'stockMovements.user'])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('order_number', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_phone', 'like', '%'.$this->search.'%')))
                ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('order_date')
                ->latest('id')
                ->paginate(12),
        ]);
    }

    private function saveOrderItemDetails(int $orderId, string $requiredStatus): void
    {
        $validated = $this->validate([
            'itemChanges' => ['required', 'array', 'min:1', 'max:50'],
            'itemChanges.*.actual_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'itemChanges.*.agreed_price' => ['nullable', 'numeric', 'gt:0', 'max:9999999999999.99'],
        ]);

        DB::transaction(function () use ($orderId, $requiredStatus, $validated): void {
            $order = CustomOrder::query()->with('items')->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== $requiredStatus) {
                throw ValidationException::withMessages(['orderStatus' => 'Detail hanya dapat diperbarui pada tahap '.$requiredStatus.'.']);
            }

            $this->saveItemChangesOnOrder($order, $validated['itemChanges']);
            $this->recordStatus($order, $requiredStatus, $requiredStatus, 'Berat aktual dan harga pesanan diperbarui.');
        });
    }

    /** @param array<int, array{actual_weight?: string|null, agreed_price?: string|null}>|null $changes */
    private function saveItemChangesOnOrder(CustomOrder $order, ?array $changes = null): void
    {
        $changes ??= $this->itemChanges;
        $total = 0.0;

        foreach ($order->items as $item) {
            $values = $changes[$item->id] ?? [];
            $weight = filled($values['actual_weight'] ?? null)
                ? (float) $values['actual_weight']
                : ($item->actual_weight !== null ? (float) $item->actual_weight : null);
            $price = filled($values['agreed_price'] ?? null)
                ? (float) $values['agreed_price']
                : ($item->agreed_price !== null && (float) $item->agreed_price > 0 ? (float) $item->agreed_price : null);

            if ($item->stock_basis === 'weight' && ($weight === null || $weight <= 0)) {
                throw ValidationException::withMessages(['itemChanges.'.$item->id.'.actual_weight' => 'Berat aktual barang wajib diisi sebelum produksi selesai.']);
            }

            if ($item->price_basis === 'weight' && $price !== null && $weight === null) {
                throw ValidationException::withMessages(['itemChanges.'.$item->id.'.actual_weight' => 'Berat aktual diperlukan untuk menghitung harga per kilogram.']);
            }

            $itemTotal = match ($item->price_basis) {
                'weight' => $weight !== null && $price !== null ? round($weight * $price, 2) : 0,
                'unit' => $price !== null ? round((float) $item->quantity * $price, 2) : 0,
                default => $price ?? 0,
            };

            $item->actual_weight = $weight;
            $item->agreed_price = $price;
            $item->total_price = $itemTotal;
            $item->save();
            $total += $itemTotal;
        }

        $firstItem = $order->items->first();
        $order->fill([
            'actual_weight' => $firstItem?->actual_weight,
            'agreed_price' => $firstItem?->agreed_price ?? 0,
            'total_price' => round($total, 2),
        ])->save();
    }

    private function loadItemChanges(CustomOrder $order): void
    {
        $this->itemChanges = $order->items->mapWithKeys(fn (CustomOrderItem $item): array => [
            $item->id => [
                'actual_weight' => $item->actual_weight !== null ? (string) $item->actual_weight : '',
                'agreed_price' => $item->agreed_price !== null && (float) $item->agreed_price > 0 ? (string) $item->agreed_price : '',
            ],
        ])->all();
    }

    private function hasItemChanges(): bool
    {
        return $this->itemChanges !== [];
    }

    private function applyStockMovement(CustomOrder $order, string $toStatus): void
    {
        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::withTrashed()->lockForUpdate()->find($item->product_id) : null;

            if ($product === null) {
                throw ValidationException::withMessages(['orderStatus' => 'Produk '.$item->product_name.' tidak lagi tersedia di katalog.']);
            }

            $movementType = $toStatus === 'ready' ? 'custom_order_ready' : 'custom_order_delivery';
            $stockDelta = $toStatus === 'ready' ? 1 : -1;
            $stockQuantity = $item->stock_basis === 'weight'
                ? (float) ($item->actual_weight ?? 0)
                : (float) $item->quantity;

            if ($toStatus === 'ready' && $item->stock_basis === 'weight' && $stockQuantity <= 0) {
                throw ValidationException::withMessages(['itemChanges.'.$item->id.'.actual_weight' => 'Berat aktual diperlukan sebelum barang ditandai siap diambil.']);
            }

            $stock = InventoryLevel::query()
                ->where('product_id', $product->id)
                ->where('inventory_type', 'finished')
                ->lockForUpdate()
                ->first();

            if ($stock === null) {
                $stock = InventoryLevel::create([
                    'product_id' => $product->id,
                    'inventory_type' => 'finished',
                    'quantity' => 0,
                ]);
            }

            $newBalance = (float) $stock->quantity + ($stockDelta * $stockQuantity);

            if ($newBalance < 0) {
                throw ValidationException::withMessages(['orderStatus' => 'Stok barang '.$item->product_name.' tidak mencukupi.']);
            }

            $stock->update(['quantity' => $newBalance]);
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'custom_order_id' => $order->id,
                'custom_order_item_id' => $item->id,
                'movement_type' => $movementType,
                'quantity' => $stockDelta * $stockQuantity,
                'balance_after' => $newBalance,
                'inventory_type' => 'finished',
                'reference' => $order->order_number,
            ]);
        }
    }

    private function recordStatus(CustomOrder $order, ?string $fromStatus, string $toStatus, ?string $reason = null): void
    {
        CustomOrderStatusHistory::create([
            'custom_order_id' => $order->id,
            'user_id' => auth()->id(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'customerName', 'customerPhone', 'productId', 'specifications', 'diameter', 'shape',
            'estimatedWeight', 'agreedPrice', 'estimatedCompletionDate', 'notes', 'orderItems',
        ]);
        $this->quantity = '1';
        $this->priceBasis = 'unit';
        $this->orderDate = now()->timezone('Asia/Jakarta')->toDateString();
        $this->createToken = (string) Str::uuid();
        $this->resetValidation();
    }
}
