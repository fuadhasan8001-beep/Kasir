<?php

namespace App\Livewire\Orders;

use App\Models\CustomOrder;
use App\Models\CustomOrderPayment;
use App\Models\CustomOrderStatusHistory;
use App\Models\InventoryLevel;
use App\Models\Product;
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

    public string $customerName = '';

    public string $customerPhone = '';

    public string $orderDate = '';

    public ?int $productId = null;

    public string $specifications = '';

    public string $diameter = '';

    public string $shape = '';

    public string $quantity = '1';

    public string $estimatedWeight = '';

    public string $priceBasis = 'fixed';

    public string $agreedPrice = '';

    public string $estimatedCompletionDate = '';

    public string $notes = '';

    public string $initialPayment = '0';

    public string $paymentMethod = 'cash';

    public string $initialPaymentMethod = 'cash';

    public string $createToken = '';

    public string $paymentToken = '';

    public string $paymentAmount = '';

    public string $actualWeight = '';

    public string $cancellationReason = '';

    public ?int $expandedOrderId = null;

    public function mount(): void
    {
        $this->orderDate = now()->timezone('Asia/Jakarta')->toDateString();
        $this->createToken = (string) Str::uuid();
        $this->paymentToken = (string) Str::uuid();
    }

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.create'), 403);
    }

    public function createOrder(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.create'), 403);

        $validated = $this->validate([
            'customerName' => ['required', 'string', 'max:160'],
            'customerPhone' => ['nullable', 'string', 'max:40'],
            'orderDate' => ['required', 'date', 'before_or_equal:today'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'specifications' => ['nullable', 'string', 'max:1000'],
            'diameter' => ['nullable', 'string', 'max:80'],
            'shape' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'estimatedWeight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'priceBasis' => ['required', Rule::in(['fixed', 'weight'])],
            'agreedPrice' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'estimatedCompletionDate' => ['nullable', 'date', 'after_or_equal:orderDate'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initialPayment' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'initialPaymentMethod' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'createToken' => ['required', 'uuid'],
        ], [
            'customerName.required' => 'Nama pelanggan wajib diisi.',
            'productId.required' => 'Pilih produk untuk pesanan ini.',
            'quantity.gt' => 'Jumlah pesanan harus lebih dari nol.',
            'estimatedWeight.gt' => 'Estimasi berat harus lebih dari nol.',
            'agreedPrice.gt' => 'Harga yang disepakati harus lebih dari nol.',
        ]);

        $product = Product::query()->with('unit')->where('is_active', true)->findOrFail($validated['productId']);
        $estimatedWeight = filled($validated['estimatedWeight']) ? (float) $validated['estimatedWeight'] : null;

        if ($validated['priceBasis'] === 'weight' && $estimatedWeight === null) {
            $this->addError('estimatedWeight', 'Masukkan estimasi berat untuk menghitung total sementara berdasarkan kilogram.');

            return;
        }

        $totalPrice = $validated['priceBasis'] === 'weight'
            ? round($estimatedWeight * (float) $validated['agreedPrice'], 2)
            : (float) $validated['agreedPrice'];

        if ((float) $validated['initialPayment'] > $totalPrice) {
            $this->addError('initialPayment', 'DP tidak boleh melebihi total sementara pesanan.');

            return;
        }

        $order = DB::transaction(function () use ($validated, $product, $estimatedWeight, $totalPrice): CustomOrder {
            $existingOrder = CustomOrder::query()->where('request_key', $validated['createToken'])->first();

            if ($existingOrder !== null) {
                return $existingOrder;
            }

            $order = CustomOrder::create([
                'order_number' => 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'request_key' => $validated['createToken'],
                'created_by' => auth()->id(),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'customer_name' => trim($validated['customerName']),
                'customer_phone' => $validated['customerPhone'] ?: null,
                'order_date' => $validated['orderDate'],
                'specifications' => filled($validated['specifications']) ? ['Rincian' => trim($validated['specifications'])] : null,
                'diameter' => $validated['diameter'] ?: null,
                'shape' => $validated['shape'] ?: null,
                'quantity' => $validated['quantity'],
                'stock_basis' => $product->pricing_method === 'weight' ? 'weight' : 'unit',
                'stock_unit' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
                'estimated_weight' => $estimatedWeight,
                'price_basis' => $validated['priceBasis'],
                'agreed_price' => $validated['agreedPrice'],
                'total_price' => $totalPrice,
                'estimated_completion_date' => $validated['estimatedCompletionDate'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'status' => 'pending',
            ]);

            $this->recordStatus($order, null, 'pending');

            if ((float) $validated['initialPayment'] > 0) {
                $order->payments()->create([
                    'user_id' => auth()->id(),
                    'request_key' => (string) Str::uuid(),
                    'amount' => $validated['initialPayment'],
                    'balance_before' => $totalPrice,
                    'balance_after' => $totalPrice - (float) $validated['initialPayment'],
                    'payment_method' => $validated['initialPaymentMethod'],
                    'paid_at' => now(),
                ]);
            }

            return $order;
        });

        $this->resetForm();
        session()->flash('status', 'Pesanan '.$order->order_number.' berhasil dibuat.');
    }

    public function recordPayment(int $orderId): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.create'), 403);

        $validated = $this->validate([
            'paymentAmount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'paymentMethod' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'paymentToken' => ['required', 'uuid'],
        ], ['paymentAmount.gt' => 'Jumlah pembayaran harus lebih dari nol.']);

        DB::transaction(function () use ($orderId, $validated): void {
            $duplicate = CustomOrderPayment::query()->where('request_key', $validated['paymentToken'])->exists();

            if ($duplicate) {
                return;
            }

            $order = CustomOrder::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status === 'canceled') {
                throw ValidationException::withMessages(['paymentAmount' => 'Pesanan yang dibatalkan tidak menerima pembayaran.']);
            }

            $paid = (float) CustomOrderPayment::query()->where('custom_order_id', $order->id)->sum('amount');
            $remaining = max(0, (float) $order->total_price - $paid);

            if ((float) $validated['paymentAmount'] > $remaining) {
                throw ValidationException::withMessages(['paymentAmount' => 'Pembayaran melebihi sisa tagihan sebesar Rp '.number_format($remaining, 0, ',', '.').'.']);
            }

            $order->payments()->create([
                'user_id' => auth()->id(),
                'request_key' => $validated['paymentToken'],
                'amount' => $validated['paymentAmount'],
                'balance_before' => $remaining,
                'balance_after' => $remaining - (float) $validated['paymentAmount'],
                'payment_method' => $validated['paymentMethod'],
                'paid_at' => now(),
            ]);
        });

        $this->paymentAmount = '';
        $this->paymentToken = (string) Str::uuid();
        $this->paymentMethod = 'cash';
        session()->flash('status', 'Pembayaran pesanan berhasil dicatat.');
    }

    public function saveActualWeight(int $orderId): void
    {
        $validated = $this->validate([
            'actualWeight' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
        ], ['actualWeight.gt' => 'Berat aktual harus lebih dari nol.']);

        DB::transaction(function () use ($orderId, $validated): void {
            $order = CustomOrder::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'made') {
                throw ValidationException::withMessages(['actualWeight' => 'Berat aktual dapat dicatat setelah pesanan selesai dibuat.']);
            }

            $previousWeight = $order->actual_weight;
            $order->actual_weight = $validated['actualWeight'];

            if ($order->price_basis === 'weight') {
                $order->total_price = round((float) $validated['actualWeight'] * (float) $order->agreed_price, 2);
            }

            $order->save();
            $this->recordStatus(
                $order,
                'made',
                'made',
                'Berat aktual diubah dari '.($previousWeight ?? 'belum diisi').' kg menjadi '.$validated['actualWeight'].' kg oleh '.auth()->user()->name.'.'.($order->price_basis === 'fixed' ? ' Harga kesepakatan tetap dipertahankan.' : ' Total dihitung ulang sesuai harga per kg.'),
            );
        });

        $this->actualWeight = '';
        session()->flash('status', 'Berat aktual pesanan berhasil disimpan.');
    }

    public function transitionOrderStatus(int $orderId, string $toStatus): void
    {
        $allowedStatuses = ['producing', 'made', 'ready', 'delivered', 'canceled'];
        abort_unless(in_array($toStatus, $allowedStatuses, true), 422);

        $validatedActualWeight = null;

        if ($toStatus === 'made' && filled($this->actualWeight)) {
            $validatedActualWeight = $this->validate([
                'actualWeight' => ['numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            ])['actualWeight'];
        }

        DB::transaction(function () use ($orderId, $toStatus, $validatedActualWeight): void {
            $order = CustomOrder::query()->lockForUpdate()->findOrFail($orderId);
            $allowedTransitions = [
                'pending' => ['producing', 'canceled'],
                'producing' => ['made', 'canceled'],
                'made' => ['ready', 'canceled'],
                'ready' => ['delivered'],
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

            $product = $order->product_id ? Product::withTrashed()->lockForUpdate()->find($order->product_id) : null;

            if ($toStatus === 'made' && ($order->price_basis === 'weight' || $order->stock_basis === 'weight') && blank($validatedActualWeight) && blank($order->actual_weight)) {
                throw ValidationException::withMessages(['actualWeight' => 'Masukkan berat aktual sebelum menandai pesanan selesai dibuat.']);
            }

            if ($toStatus === 'made' && $validatedActualWeight !== null) {
                $order->actual_weight = $validatedActualWeight;

                if ($order->price_basis === 'weight') {
                    $order->total_price = round((float) $validatedActualWeight * (float) $order->agreed_price, 2);
                }
            }

            if ($toStatus === 'ready' || $toStatus === 'delivered') {
                $this->applyStockMovement($order, $toStatus, $product);
            }

            $fromStatus = $order->status;
            $order->status = $toStatus;
            $order->save();
            $reason = $toStatus === 'canceled' ? trim($this->cancellationReason) : null;

            if ($toStatus === 'made' && $validatedActualWeight !== null) {
                $reason = 'Berat aktual '.$validatedActualWeight.' kg dicatat oleh '.auth()->user()->name.'.'.($order->price_basis === 'fixed' ? ' Harga kesepakatan tetap dipertahankan.' : ' Total dihitung ulang sesuai harga per kg.');
            }

            $this->recordStatus(
                $order,
                $fromStatus,
                $toStatus,
                $reason,
            );
        });

        if ($toStatus === 'made') {
            $this->actualWeight = '';
        }

        if ($toStatus === 'canceled') {
            $this->cancellationReason = '';
        }

        session()->flash('status', 'Status pesanan berhasil diperbarui.');
    }

    public function toggleDetails(int $orderId): void
    {
        $this->expandedOrderId = $this->expandedOrderId === $orderId ? null : $orderId;
        $this->reset('paymentAmount', 'actualWeight', 'cancellationReason');
        $this->resetValidation();
    }

    public function showDetails(int $orderId): void
    {
        $this->expandedOrderId = $orderId;
        $this->reset('paymentAmount', 'actualWeight', 'cancellationReason');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.orders.manage-custom-orders', [
            'products' => Product::query()->with('unit')->where('is_active', true)->orderBy('name')->get(),
            'orders' => CustomOrder::query()
                ->with(['creator', 'payments.user', 'statusHistories.user', 'stockMovements.user'])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('order_number', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_phone', 'like', '%'.$this->search.'%')))
                ->latest('order_date')
                ->latest('id')
                ->paginate(12),
        ]);
    }

    private function applyStockMovement(CustomOrder $order, string $toStatus, ?Product $product): void
    {
        if ($product === null) {
            throw ValidationException::withMessages(['orderStatus' => 'Produk pesanan tidak lagi tersedia di katalog.']);
        }

        $movementType = $toStatus === 'ready' ? 'custom_order_ready' : 'custom_order_delivery';
        $stockDelta = $toStatus === 'ready' ? 1 : -1;
        $stockQuantity = $order->stock_basis === 'weight'
            ? (float) $order->actual_weight
            : (float) $order->quantity;
        $stock = InventoryLevel::query()
            ->where('product_id', $product->id)
            ->where('inventory_type', 'finished')
            ->lockForUpdate()
            ->first();

        if ($toStatus === 'ready' && $order->stock_basis === 'weight' && (float) $order->actual_weight <= 0) {
            throw ValidationException::withMessages(['actualWeight' => 'Berat aktual harus diisi sebelum barang dinyatakan siap.']);
        }

        if ($stock === null) {
            $stock = InventoryLevel::create([
                'product_id' => $product->id,
                'inventory_type' => 'finished',
                'quantity' => 0,
            ]);
        }

        $newBalance = (float) $stock->quantity + ($stockDelta * $stockQuantity);

        if ($newBalance < 0) {
            throw ValidationException::withMessages(['orderStatus' => 'Stok barang jadi tidak mencukupi untuk menyerahkan pesanan ini.']);
        }

        $stock->update(['quantity' => $newBalance]);
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'custom_order_id' => $order->id,
            'movement_type' => $movementType,
            'quantity' => $stockDelta * $stockQuantity,
            'balance_after' => $newBalance,
            'inventory_type' => 'finished',
            'reference' => $order->order_number,
        ]);
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
            'estimatedWeight', 'agreedPrice', 'estimatedCompletionDate', 'notes', 'initialPayment',
        ]);
        $this->quantity = '1';
        $this->priceBasis = 'fixed';
        $this->initialPayment = '0';
        $this->paymentMethod = 'cash';
        $this->initialPaymentMethod = 'cash';
        $this->orderDate = now()->timezone('Asia/Jakarta')->toDateString();
        $this->createToken = (string) Str::uuid();
        $this->resetValidation();
    }
}
