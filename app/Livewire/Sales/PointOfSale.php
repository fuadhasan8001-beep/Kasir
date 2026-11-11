<?php

namespace App\Livewire\Sales;

use App\Models\Category;
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

    public string $categoryFilter = '';

    public string $customerName = '';

    public ?int $productId = null;

    public string $quantity = '1';

    public string $actualWeight = '';

    public string $priceBasis = 'unit';

    public string $unitPrice = '';

    public string $diameter = '';

    public string $length = '';

    public string $width = '';

    public string $customSpecification = '';

    public string $orderNotes = '';

    public array $cart = [];

    public string $amountReceived = '';

    public string $checkoutToken = '';

    public bool $showConfirmation = false;

    public ?string $completedInvoice = null;

    /** @var array{items: array<int, array{name: string, quantity: float, unit: string, weight: ?float, total: float}>, total: float, received: float, change: float}|null */
    public ?array $completedReceipt = null;

    public function mount(): void
    {
        $this->checkoutToken = (string) Str::uuid();
    }

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetValidation();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetValidation();
    }

    public function updatedProductId(): void
    {
        $product = $this->productId ? Product::query()->find($this->productId) : null;
        $this->priceBasis = $product?->pricing_method === 'weight' ? 'weight' : 'unit';
        $this->unitPrice = $product ? (string) $product->selling_price : '';
        $this->resetValidation();
    }

    public function addItem(): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'actualWeight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'priceBasis' => ['required', Rule::in(['weight', 'unit', 'custom'])],
            'unitPrice' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'diameter' => ['nullable', 'string', 'max:80'],
            'length' => ['nullable', 'string', 'max:80'],
            'width' => ['nullable', 'string', 'max:80'],
            'customSpecification' => ['nullable', 'string', 'max:500'],
            'orderNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            'productId.required' => 'Pilih barang terlebih dahulu.',
            'quantity.gt' => 'Jumlah harus lebih dari nol.',
            'actualWeight.gt' => 'Berat aktual harus lebih dari nol.',
            'unitPrice.required' => 'Masukkan harga yang disepakati.',
            'unitPrice.gt' => 'Harga harus lebih dari nol.',
        ]);

        $product = Product::query()
            ->with(['unit', 'category', 'inventoryLevels'])
            ->where('is_active', true)
            ->findOrFail($validated['productId']);

        if ($product->pricing_method === 'weight' && blank($validated['actualWeight'])) {
            $this->addError('actualWeight', 'Berat aktual wajib diisi untuk barang yang stoknya dihitung per kilogram.');

            return;
        }

        if ($validated['priceBasis'] === 'weight' && blank($validated['actualWeight'])) {
            $this->addError('actualWeight', 'Masukkan berat aktual untuk menghitung harga per kilogram.');

            return;
        }

        $lineTotal = match ($validated['priceBasis']) {
            'weight' => (float) $validated['actualWeight'] * (float) $validated['unitPrice'],
            'custom' => (float) $validated['unitPrice'],
            default => (float) $validated['quantity'] * (float) $validated['unitPrice'],
        };
        $stockQuantity = $product->pricing_method === 'weight'
            ? (float) $validated['actualWeight']
            : (float) $validated['quantity'];
        $unitName = $validated['priceBasis'] === 'weight'
            ? 'kg'
            : $product->unit->abbreviation;

        $this->cart[] = [
            'key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_name' => $product->category->name,
            'quantity' => (float) $validated['quantity'],
            'actual_weight' => filled($validated['actualWeight']) ? (float) $validated['actualWeight'] : null,
            'stock_quantity' => $stockQuantity,
            'stock_unit' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
            'unit_name' => $unitName,
            'pricing_basis' => $validated['priceBasis'],
            'unit_price' => (float) $validated['unitPrice'],
            'line_total' => round($lineTotal, 2),
            'specifications' => array_filter([
                'Diameter' => $validated['diameter'],
                'Panjang' => $validated['length'],
                'Lebar' => $validated['width'],
                'Ukuran custom' => $validated['customSpecification'],
            ]),
            'notes' => $validated['orderNotes'],
        ];

        $this->reset([
            'productId', 'actualWeight', 'unitPrice', 'diameter', 'length', 'width',
            'customSpecification', 'orderNotes',
        ]);
        $this->quantity = '1';
        $this->priceBasis = 'unit';
        $this->resetValidation();
    }

    public function editItem(string $key): void
    {
        $item = collect($this->cart)->firstWhere('key', $key);

        if ($item === null) {
            return;
        }

        $this->productId = $item['product_id'];
        $this->quantity = (string) $item['quantity'];
        $this->actualWeight = $item['actual_weight'] !== null ? (string) $item['actual_weight'] : '';
        $this->priceBasis = $item['pricing_basis'];
        $this->unitPrice = (string) $item['unit_price'];
        $this->diameter = $item['specifications']['Diameter'] ?? '';
        $this->length = $item['specifications']['Panjang'] ?? '';
        $this->width = $item['specifications']['Lebar'] ?? '';
        $this->customSpecification = $item['specifications']['Ukuran custom'] ?? '';
        $this->orderNotes = $item['notes'] ?? '';
        $this->removeItem($key);
    }

    public function removeItem(string $key): void
    {
        $this->cart = array_values(array_filter($this->cart, fn (array $item): bool => $item['key'] !== $key));
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->amountReceived = '';
        $this->showConfirmation = false;
        $this->resetValidation();
    }

    public function startCheckout(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Tambahkan minimal satu barang ke transaksi.');

            return;
        }

        $this->showConfirmation = true;
        $this->resetValidation();
    }

    public function cancelCheckout(): void
    {
        $this->showConfirmation = false;
        $this->resetValidation();
    }

    public function checkout(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Tambahkan minimal satu barang ke transaksi.');

            return;
        }

        $validated = $this->validate([
            'customerName' => ['nullable', 'string', 'max:160'],
            'amountReceived' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'checkoutToken' => ['required', 'uuid'],
            'cart' => ['required', 'array', 'min:1', 'max:100'],
            'cart.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'cart.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.actual_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.stock_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'cart.*.pricing_basis' => ['required', Rule::in(['weight', 'unit', 'custom'])],
            'cart.*.unit_price' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'cart.*.line_total' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'cart.*.specifications' => ['array', 'max:10'],
            'cart.*.specifications.*' => ['string', 'max:500'],
            'cart.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalizedCart = [];

        foreach ($validated['cart'] as $item) {
            $weight = filled($item['actual_weight']) ? (float) $item['actual_weight'] : null;
            $quantity = (float) $item['quantity'];

            if ($item['pricing_basis'] === 'weight' && $weight === null) {
                throw ValidationException::withMessages(['cart' => 'Berat aktual wajib diisi untuk harga per kilogram.']);
            }

            $lineTotal = match ($item['pricing_basis']) {
                'weight' => $weight * (float) $item['unit_price'],
                'custom' => (float) $item['unit_price'],
                default => $quantity * (float) $item['unit_price'],
            };

            $normalizedCart[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'actual_weight' => $weight,
                'pricing_basis' => $item['pricing_basis'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => round($lineTotal, 2),
                'specifications' => $item['specifications'] ?? [],
                'notes' => $item['notes'] ?? null,
            ];
        }

        $total = round(array_sum(array_column($normalizedCart, 'line_total')), 2);

        if ((float) $validated['amountReceived'] < $total) {
            $this->addError('amountReceived', 'Uang diterima belum mencukupi total transaksi.');

            return;
        }

        $sale = DB::transaction(function () use ($validated, $normalizedCart, $total): Sale {
            $existingSale = Sale::query()->where('request_key', $validated['checkoutToken'])->first();

            if ($existingSale !== null) {
                return $existingSale->load('items');
            }

            $sale = Sale::create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'request_key' => $validated['checkoutToken'],
                'user_id' => auth()->id(),
                'customer_name' => filled($validated['customerName']) ? trim($validated['customerName']) : null,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'amount_received' => $validated['amountReceived'],
                'change_amount' => round((float) $validated['amountReceived'] - $total, 2),
                'payment_status' => 'paid',
            ]);

            foreach ($normalizedCart as $item) {
                $product = Product::query()->with('unit')->lockForUpdate()->findOrFail($item['product_id']);

                if (! $product->is_active) {
                    throw ValidationException::withMessages(['cart' => 'Barang '.$product->name.' sudah tidak aktif.']);
                }

                $stockQuantity = $product->pricing_method === 'weight'
                    ? (float) ($item['actual_weight'] ?? 0)
                    : (float) $item['quantity'];

                if ($stockQuantity <= 0) {
                    throw ValidationException::withMessages(['cart' => 'Berat aktual wajib diisi untuk barang '.$product->name.'.']);
                }

                $stock = InventoryLevel::query()
                    ->where('product_id', $product->id)
                    ->where('inventory_type', 'finished')
                    ->lockForUpdate()
                    ->first();

                if ($stock === null || (float) $stock->quantity < $stockQuantity) {
                    throw ValidationException::withMessages(['cart' => 'Stok '.$product->name.' tidak mencukupi. Periksa inventaris sebelum menyimpan transaksi.']);
                }

                $stock->decrement('quantity', $stockQuantity);
                $balance = (float) $stock->fresh()->quantity;
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_name' => $item['pricing_basis'] === 'weight' ? 'kg' : $product->unit->abbreviation,
                    'pricing_basis' => $item['pricing_basis'],
                    'quantity' => $item['quantity'],
                    'actual_weight' => $item['actual_weight'],
                    'specifications' => $item['specifications'],
                    'notes' => $item['notes'] ?: null,
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $product->purchase_price,
                    'line_total' => $item['line_total'],
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'sale_id' => $sale->id,
                    'movement_type' => 'sale',
                    'quantity' => -1 * $stockQuantity,
                    'balance_after' => $balance,
                    'inventory_type' => 'finished',
                    'reference' => $sale->invoice_number,
                ]);
            }

            return $sale->load('items');
        });

        $this->completedInvoice = $sale->invoice_number;
        $this->completedReceipt = [
            'items' => $sale->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit_name,
                'weight' => $item->actual_weight !== null ? (float) $item->actual_weight : null,
                'total' => (float) $item->line_total,
            ])->all(),
            'total' => (float) $sale->total,
            'received' => (float) $sale->amount_received,
            'change' => (float) $sale->change_amount,
        ];
        $this->cart = [];
        $this->customerName = '';
        $this->amountReceived = '';
        $this->checkoutToken = (string) Str::uuid();
        $this->showConfirmation = false;
        session()->flash('status', 'Transaksi berhasil disimpan.');
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['unit', 'category', 'specificationValues.specification', 'inventoryLevels'])
            ->where('is_active', true)
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('sku', 'like', '%'.$this->search.'%')
                ->orWhere('barcode', 'like', '%'.$this->search.'%')))
            ->when($this->categoryFilter, fn ($query) => $query->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->limit(60)
            ->get();

        return view('livewire.sales.point-of-sale', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'subtotal' => round(array_sum(array_column($this->cart, 'line_total')), 2),
            'totalItems' => array_sum(array_column($this->cart, 'quantity')),
            'totalWeight' => array_sum(array_map(fn (array $item): float => (float) ($item['actual_weight'] ?? 0), $this->cart)),
        ]);
    }
}
