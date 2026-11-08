<?php

namespace App\Livewire\Sales;

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

class PointOfSale extends Component
{
    public string $search = '';

    public ?int $productId = null;

    public string $quantity = '1';

    public string $actualWeight = '';

    public string $diameter = '';

    public string $length = '';

    public string $width = '';

    public string $orderNotes = '';

    public array $cart = [];

    public string $discount = '0';

    public string $paymentMethod = 'cash';

    public string $amountReceived = '';

    public ?string $completedInvoice = null;

    public ?array $completedReceipt = null;

    public string $checkoutToken = '';

    public function mount(): void
    {
        $this->checkoutToken = (string) Str::uuid();
    }

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.create'), 403);
    }

    public function addItem(): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'actualWeight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'diameter' => ['nullable', 'string', 'max:80'],
            'length' => ['nullable', 'string', 'max:80'],
            'width' => ['nullable', 'string', 'max:80'],
            'orderNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            'productId.required' => 'Pilih barang terlebih dahulu.',
            'quantity.gt' => 'Jumlah harus lebih dari nol.',
            'actualWeight.gt' => 'Berat aktual harus lebih dari nol.',
        ]);

        $product = Product::query()->with('unit')->where('is_active', true)->findOrFail($validated['productId']);

        if ($product->pricing_method === 'weight' && blank($validated['actualWeight'])) {
            $this->addError('actualWeight', 'Masukkan berat aktual barang dalam kg.');

            return;
        }

        $billableQuantity = $product->pricing_method === 'weight'
            ? (float) $validated['actualWeight']
            : (float) $validated['quantity'];

        $this->cart[] = [
            'key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
            'pricing_method' => $product->pricing_method,
            'quantity' => (float) $validated['quantity'],
            'actual_weight' => $validated['actualWeight'] !== '' ? (float) $validated['actualWeight'] : null,
            'billable_quantity' => $billableQuantity,
            'unit_price' => (float) $product->selling_price,
            'line_total' => round($billableQuantity * (float) $product->selling_price, 2),
            'specifications' => array_filter([
                'Diameter' => $validated['diameter'],
                'Panjang' => $validated['length'],
                'Lebar' => $validated['width'],
            ]),
            'notes' => $validated['orderNotes'],
        ];

        $this->reset(['productId', 'quantity', 'actualWeight', 'diameter', 'length', 'width', 'orderNotes']);
        $this->quantity = '1';
        $this->resetValidation();
    }

    public function removeItem(string $key): void
    {
        $this->cart = array_values(array_filter($this->cart, fn (array $item): bool => $item['key'] !== $key));
    }

    public function checkout(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.create'), 403);

        if ($this->cart === []) {
            $this->addError('cart', 'Tambahkan minimal satu barang ke transaksi.');

            return;
        }

        $validated = $this->validate([
            'discount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'paymentMethod' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'amountReceived' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'checkoutToken' => ['required', 'uuid'],
        ]);

        if ((float) $validated['discount'] > 0) {
            abort_unless(auth()->user()?->hasPermission('finance.manage'), 403);
        }

        $this->validate([
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'cart.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.actual_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.specifications' => ['array', 'max:10'],
            'cart.*.specifications.*' => ['string', 'max:80'],
            'cart.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sale = DB::transaction(function () use ($validated): Sale {
            $existingSale = Sale::query()->where('request_key', $this->checkoutToken)->first();

            if ($existingSale !== null) {
                return $existingSale->load('items');
            }

            $lockedProducts = [];
            $subtotal = 0.0;

            foreach ($this->cart as $item) {
                $product = Product::query()->with('unit')->lockForUpdate()->findOrFail($item['product_id']);
                if (! $product->is_active) {
                    throw ValidationException::withMessages(['cart' => 'Barang '.$product->name.' sudah tidak aktif.']);
                }

                if ($product->pricing_method === 'weight' && blank($item['actual_weight'])) {
                    throw ValidationException::withMessages(['cart' => 'Berat aktual '.$product->name.' wajib diisi.']);
                }

                $lockedProducts[$product->id] = $product;
                $billableQuantity = $product->pricing_method === 'weight'
                    ? (float) $item['actual_weight']
                    : (float) $item['quantity'];
                $subtotal += round($billableQuantity * (float) $product->selling_price, 2);
            }

            $subtotal = round($subtotal, 2);
            $total = round($subtotal - (float) $validated['discount'], 2);

            if ($total < 0) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
            }

            if ((float) $validated['amountReceived'] < $total) {
                throw ValidationException::withMessages(['amountReceived' => 'Uang diterima belum mencukupi total pembayaran.']);
            }

            $sale = Sale::create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'request_key' => $this->checkoutToken,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => $validated['discount'],
                'total' => $total,
                'payment_method' => $validated['paymentMethod'],
                'amount_received' => $validated['amountReceived'],
                'change_amount' => (float) $validated['amountReceived'] - $total,
                'payment_status' => 'paid',
            ]);

            foreach ($this->cart as $item) {
                $product = $lockedProducts[$item['product_id']];
                $stock = InventoryLevel::query()
                    ->where('product_id', $product->id)
                    ->where('inventory_type', 'finished')
                    ->lockForUpdate()
                    ->first();
                $stockQuantity = $product->pricing_method === 'weight'
                    ? (float) $item['actual_weight']
                    : (float) $item['quantity'];

                if ($stock === null || (float) $stock->quantity < $stockQuantity) {
                    throw ValidationException::withMessages(['cart' => 'Stok '.$product->name.' tidak mencukupi. Periksa inventaris sebelum menyimpan transaksi.']);
                }

                $stock->decrement('quantity', $stockQuantity);
                $balance = (float) $stock->fresh()->quantity;
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_name' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
                    'quantity' => $item['quantity'],
                    'actual_weight' => $product->pricing_method === 'weight' ? $item['actual_weight'] : null,
                    'specifications' => $item['specifications'],
                    'notes' => $item['notes'] ?: null,
                    'unit_price' => $product->selling_price,
                    'unit_cost' => $product->purchase_price,
                    'line_total' => round($stockQuantity * (float) $product->selling_price, 2),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'sale_id' => $sale->id,
                    'movement_type' => 'sale',
                    'quantity' => -$stockQuantity,
                    'balance_after' => $balance,
                    'inventory_type' => 'finished',
                    'reference' => $sale->invoice_number,
                ]);
            }

            return $sale;
        });

        $this->completedInvoice = $sale->invoice_number;
        $this->completedReceipt = [
            'items' => $sale->items()->get()->map(fn ($item): array => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit' => $item->unit_name,
                'weight' => $item->actual_weight,
                'total' => $item->line_total,
            ])->all(),
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount,
            'total' => $sale->total,
            'payment_method' => $sale->payment_method,
            'amount_received' => $sale->amount_received,
            'change_amount' => $sale->change_amount,
        ];
        $this->cart = [];
        $this->discount = '0';
        $this->amountReceived = '';
        $this->checkoutToken = (string) Str::uuid();
        session()->flash('status', 'Transaksi berhasil disimpan.');
    }

    public function render(): View
    {
        return view('livewire.sales.point-of-sale', [
            'products' => Product::query()
                ->with('unit')
                ->where('is_active', true)
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%')))
                ->orderBy('name')
                ->limit(30)
                ->get(),
            'subtotal' => array_sum(array_column($this->cart, 'line_total')),
            'total' => max(0, array_sum(array_column($this->cart, 'line_total')) - (float) $this->discount),
        ]);
    }
}
