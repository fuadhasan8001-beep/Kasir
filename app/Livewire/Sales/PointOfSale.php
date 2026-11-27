<?php

namespace App\Livewire\Sales;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public string $shape = '';

    public string $length = '';

    public string $width = '';

    public string $customSpecification = '';

    public string $orderNotes = '';

    public array $specificationInputs = [];

    public array $customSpecificationInputs = [];

    public string $sizePresetId = '';

    public string $diameterPresetId = '';

    public ?string $editingCartKey = null;

    public bool $showProductDetails = false;

    public array $cart = [];

    public string $amountReceived = '';

    public string $checkoutToken = '';

    public bool $showConfirmation = false;

    public ?string $completedInvoice = null;

    /** @var array{items: array<int, array{name: string, quantity: float, unit: string, weight: ?float, total: float}>, total: float, received: float, change: float}|null */
    public ?array $completedReceipt = null;

    public function mount(): void
    {
        $cartState = session()->get($this->cartSessionKey(), []);
        $this->cart = $cartState['items'] ?? [];
        $this->customerName = $cartState['customerName'] ?? '';
        $this->amountReceived = $cartState['amountReceived'] ?? '';
        $this->checkoutToken = $cartState['checkoutToken'] ?? (string) Str::uuid();
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

    public function updatedCustomerName(): void
    {
        $this->persistCart();
    }

    public function updatedAmountReceived(): void
    {
        $this->persistCart();
    }

    public function updatedProductId(): void
    {
        $product = $this->productId ? Product::query()->with('category')->find($this->productId) : null;

        if ($product === null) {
            $this->resetProductForm();
        } else {
            $this->prepareProductForm($product);
        }

        $this->resetValidation();
    }

    public function updatedSizePresetId(string $value): void
    {
        $this->resetValidation();
        $this->orderNotes = '';
        $selectedDiameter = $this->specificationInputs['diameter_besi'] ?? '';
        $this->specificationInputs = [];
        $this->customSpecificationInputs = [];

        if ($this->productId === null || $value === '') {
            return;
        }

        $product = Product::query()->with(['category.specifications', 'variants'])->findOrFail($this->productId);

        if ($value === '__custom__') {
            $this->specificationInputs = $product->category->specifications
                ->whereIn('key', ['bentuk', 'panjang', 'lebar', 'diameter_besi'])
                ->mapWithKeys(fn (ProductSpecification $specification): array => [$specification->key => '__custom__'])
                ->all();

            return;
        }

        if (str_starts_with($value, 'dimension:')) {
            $index = filter_var(substr($value, strlen('dimension:')), FILTER_VALIDATE_INT);
            $dimensionSet = $index === false ? null : ($this->dimensionSetsForCategory($product->category)[$index] ?? null);

            if ($dimensionSet === null) {
                $this->sizePresetId = '';
                $this->addError('sizePresetId', 'Ukuran ini sudah tidak tersedia.');

                return;
            }

            $fieldByKey = [
                'bentuk' => 'shape',
                'panjang' => 'length',
                'lebar' => 'width',
            ];
            $this->specificationInputs = $product->category->specifications
                ->mapWithKeys(fn (ProductSpecification $specification): array => array_key_exists($specification->key, $fieldByKey)
                    ? [$specification->key => (string) ($dimensionSet[$fieldByKey[$specification->key]] ?? '')]
                    : [$specification->key => ($specification->key === 'diameter_besi' ? $selectedDiameter : '')])
                ->all();
            $this->resetValidation('sizePresetId');

            return;
        }

        $variant = $product->variants->firstWhere('id', (int) $value);

        if ($variant === null || ! $variant->is_active) {
            $this->sizePresetId = '';
            $this->addError('sizePresetId', 'Pilihan ukuran sudah tidak tersedia.');

            return;
        }

        $this->specificationInputs = $variant->specifications;
        $this->resetValidation('sizePresetId');
    }

    public function selectSizePreset(string $sizePresetId): void
    {
        $this->sizePresetId = $sizePresetId;
        $this->updatedSizePresetId($sizePresetId);
    }

    public function selectDiameterPreset(string $diameterPresetId): void
    {
        $this->diameterPresetId = $diameterPresetId;
        $this->updatedDiameterPresetId($diameterPresetId);
    }

    public function updatedDiameterPresetId(string $value): void
    {
        if (! str_starts_with($value, 'diameter:') || $this->productId === null) {
            return;
        }

        $index = filter_var(substr($value, strlen('diameter:')), FILTER_VALIDATE_INT);
        $product = Product::query()->with('category.specifications')->findOrFail($this->productId);
        $dimensionSet = $index === false ? null : ($this->dimensionSetsForCategory($product->category)[$index] ?? null);

        if ($dimensionSet === null || blank($dimensionSet['diameter'] ?? null)) {
            $this->diameterPresetId = '';
            $this->addError('diameterPresetId', 'Diameter ini sudah tidak tersedia.');

            return;
        }

        $this->specificationInputs['diameter_besi'] = (string) $dimensionSet['diameter'];
        unset($this->customSpecificationInputs['diameter_besi']);
        $this->resetValidation('diameterPresetId');
    }

    public function clearProductSelection(): void
    {
        $this->showProductDetails = false;
        $this->editingCartKey = null;
        $this->resetProductForm();
        $this->resetValidation();
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->with(['category', 'inventoryLevels'])->where('is_active', true)->findOrFail($productId);

        $this->productId = $product->id;
        $this->prepareProductForm($product);
        $this->showProductDetails = true;
        $this->resetValidation();
    }

    public function addItem(): void
    {
        $wasEditing = $this->editingCartKey !== null;

        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'actualWeight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'orderNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            'productId.required' => 'Pilih barang terlebih dahulu.',
            'quantity.gt' => 'Jumlah harus lebih dari nol.',
            'actualWeight.gt' => 'Berat aktual harus lebih dari nol.',
        ]);

        $product = Product::query()
            ->with(['unit', 'category.specifications', 'variants', 'inventoryLevels'])
            ->where('is_active', true)
            ->findOrFail($validated['productId']);

        if ($this->hasConfiguredSizeOptions($product->category) && ! str_starts_with($this->sizePresetId, 'dimension:') && $this->sizePresetId !== '__custom__') {
            throw ValidationException::withMessages(['sizePresetId' => 'Pilih ukuran bentuk atau panjang terlebih dahulu.']);
        }
        if (collect($this->dimensionSetsForCategory($product->category))->contains(fn (array $set): bool => filled($set['diameter'] ?? null))
            && ! str_starts_with($this->diameterPresetId, 'diameter:')
            && ($normalizedDiameter = trim($this->specificationInputs['diameter_besi'] ?? '')) === '') {
            throw ValidationException::withMessages(['diameterPresetId' => 'Pilih diameter besi terlebih dahulu.']);
        }

        if (str_starts_with($this->sizePresetId, 'dimension:')) {
            $dimensionIndex = filter_var(substr($this->sizePresetId, strlen('dimension:')), FILTER_VALIDATE_INT);
            $dimensionSet = $dimensionIndex === false ? null : ($this->dimensionSetsForCategory($product->category)[$dimensionIndex] ?? null);

            if ($dimensionSet === null) {
                throw ValidationException::withMessages(['sizePresetId' => 'Pilih ukuran yang masih tersedia.']);
            }

            foreach (['bentuk' => 'shape', 'panjang' => 'length', 'lebar' => 'width'] as $key => $field) {
                if (trim($this->specificationInputs[$key] ?? '') !== trim($dimensionSet[$field] ?? '')) {
                    throw ValidationException::withMessages(['sizePresetId' => 'Pilihan ukuran berubah. Pilih ukuran dari daftar kembali.']);
                }
            }
        }

        $normalizedSpecifications = $this->normalizeSpecifications(
            $product->category->specifications,
            $this->specificationInputs,
            $this->customSpecificationInputs,
        );

        $actualWeight = filled($validated['actualWeight']) ? (float) $validated['actualWeight'] : null;
        $pricingBasis = $product->pricing_method === 'weight' ? 'weight' : 'unit';
        $quantity = $pricingBasis === 'weight'
            ? $actualWeight
            : (filled($validated['quantity']) ? (float) $validated['quantity'] : null);
        $amount = $pricingBasis === 'weight' ? $actualWeight : $quantity;

        if ($amount === null || $amount <= 0) {
            $this->addError($pricingBasis === 'weight' ? 'actualWeight' : 'quantity', $pricingBasis === 'weight'
                ? 'Masukkan berat dalam kilogram.'
                : 'Masukkan jumlah yang akan dijual.');

            return;
        }

        $matchedVariant = $this->findMatchingVariant($product, $normalizedSpecifications['values'], $normalizedSpecifications['custom_keys']);
        $unitPrice = $matchedVariant ? (float) $matchedVariant->selling_price : (float) $product->selling_price;

        if ($this->hasConfiguredDiameterPrices($product->category)) {
            $dimensionPrice = $this->dimensionPriceForSpecifications($product->category, $normalizedSpecifications['values']);

            if ($dimensionPrice === null) {
                throw ValidationException::withMessages(['sizePresetId' => 'Harga untuk diameter ini belum diatur. Lengkapi harga per kg di Atribut Kategori.']);
            }

            $unitPrice = $dimensionPrice;
        }

        if ($product->variants->where('is_active', true)->isNotEmpty() && $matchedVariant === null && $normalizedSpecifications['custom_keys'] === []) {
            $this->addError('specificationInputs', 'Harga variasi ini belum diatur admin. Pilih ukuran standar atau minta admin mengatur harga custom.');

            return;
        }

        $stockQuantity = $product->pricing_method === 'weight'
            ? (float) $actualWeight
            : (float) $quantity;
        $availableStock = (float) ($product->inventoryLevels->firstWhere('inventory_type', 'finished')?->quantity ?? 0);

        if ($stockQuantity > $availableStock) {
            $this->addError('quantity', 'Jumlah melebihi stok tersedia ('.rtrim(rtrim(number_format($availableStock, 3, ',', '.'), '0'), ',').' '.$product->unit->abbreviation.').');

            return;
        }

        $unitName = $pricingBasis === 'weight'
            ? 'kg'
            : ($product->pricing_method === 'bar' ? 'batang' : $product->unit->abbreviation);

        $cartItem = [
            'key' => $this->editingCartKey ?? (string) Str::uuid(),
            'product_id' => $product->id,
            'variant_id' => $matchedVariant?->id,
            'product_name' => $product->name,
            'image_path' => $product->image_path,
            'category_name' => $product->category->name,
            'quantity' => $quantity,
            'actual_weight' => $actualWeight,
            'stock_quantity' => $stockQuantity,
            'stock_unit' => $product->pricing_method === 'weight' ? 'kg' : $product->unit->abbreviation,
            'unit_name' => $unitName,
            'pricing_basis' => $pricingBasis,
            'unit_price' => $unitPrice,
            'line_total' => round((float) $amount * $unitPrice, 2),
            'specification_values' => $normalizedSpecifications['values'],
            'specification_custom_keys' => $normalizedSpecifications['custom_keys'],
            'specifications' => $normalizedSpecifications['labels'],
            'notes' => $validated['orderNotes'],
        ];

        $matchingCartIndex = $this->findMatchingCartItemIndex($cartItem);

        if ($matchingCartIndex !== null) {
            $matchingItemKey = $this->cart[$matchingCartIndex]['key'];
            $matchingItem = $this->combineCartItems($this->cart[$matchingCartIndex], $cartItem, $product);
            $this->cart = array_map(
                fn (array $item): array => $item['key'] === $matchingItemKey ? $matchingItem : $item,
                $this->cart,
            );

            if ($this->editingCartKey !== null) {
                $this->cart = array_values(array_filter($this->cart, fn (array $item): bool => $item['key'] !== $this->editingCartKey));
            }
        } elseif ($this->editingCartKey !== null) {
            $this->cart = array_map(fn (array $item): array => $item['key'] === $this->editingCartKey ? $cartItem : $item, $this->cart);
        } else {
            $this->cart[] = $cartItem;
        }

        $this->reset([
            'productId', 'actualWeight', 'unitPrice', 'diameter', 'shape', 'length', 'width', 'diameterPresetId',
            'customSpecification', 'orderNotes', 'editingCartKey', 'specificationInputs', 'customSpecificationInputs', 'sizePresetId',
        ]);
        $this->showProductDetails = false;
        $this->quantity = '';
        $this->priceBasis = 'unit';
        $this->resetValidation();
        $this->persistCart();
        $this->dispatch('purchase-added', message: $wasEditing
            ? 'Perubahan pembelian berhasil disimpan.'
            : 'Pembelian '.$product->name.' berhasil ditambahkan.');
    }

    public function editItem(string $key): void
    {
        $item = collect($this->cart)->firstWhere('key', $key);

        if ($item === null) {
            return;
        }

        $this->productId = $item['product_id'];
        $this->editingCartKey = $item['key'];
        $this->quantity = (string) $item['quantity'];
        $this->actualWeight = $item['actual_weight'] !== null ? (string) $item['actual_weight'] : '';
        $this->priceBasis = $item['pricing_basis'];
        $this->unitPrice = (string) $item['unit_price'];
        $this->specificationInputs = $item['specification_values'] ?? [];
        $this->customSpecificationInputs = $item['specification_values'] ?? [];
        $this->sizePresetId = '';

        foreach ($item['specification_custom_keys'] ?? [] as $key) {
            $this->specificationInputs[$key] = '__custom__';
        }
        $this->orderNotes = $item['notes'] ?? '';
        $this->showProductDetails = true;
        $this->resetValidation();
    }

    public function removeItem(string $key): void
    {
        $this->cart = array_values(array_filter($this->cart, fn (array $item): bool => $item['key'] !== $key));

        if ($this->editingCartKey === $key) {
            $this->clearProductSelection();
        }

        $this->persistCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->amountReceived = '';
        $this->showConfirmation = false;
        $this->showProductDetails = false;
        $this->editingCartKey = null;
        $this->resetValidation();
        session()->forget($this->cartSessionKey());
    }

    public function startCheckout(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Tambahkan minimal satu barang ke transaksi.');

            return;
        }

        if (collect($this->cartForDisplay())->contains(fn (array $item): bool => ! $item['price_available'])) {
            $this->addError('cart', 'Harga salah satu variasi sudah tidak tersedia. Edit barang tersebut sebelum melanjutkan.');

            return;
        }

        $this->showConfirmation = true;
        $this->showProductDetails = false;
        $this->resetValidation();
    }

    public function cancelCheckout(): void
    {
        $this->showConfirmation = false;
        $this->showProductDetails = false;
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
            'cart.*.quantity' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.actual_weight' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
            'cart.*.specification_values' => ['nullable', 'array', 'max:30'],
            'cart.*.specification_values.*' => ['string', 'max:500'],
            'cart.*.specification_custom_keys' => ['nullable', 'array', 'max:30'],
            'cart.*.specification_custom_keys.*' => ['string', 'max:80'],
            'cart.*.specifications.*' => ['string', 'max:500'],
            'cart.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $sale = DB::transaction(function () use ($validated): Sale {
                $existingSale = Sale::query()->where('request_key', $validated['checkoutToken'])->first();

                if ($existingSale !== null) {
                    return $existingSale->load('items');
                }

                $normalizedCart = [];

                foreach ($validated['cart'] as $item) {
                    $product = Product::withTrashed()
                        ->with(['unit', 'category.specifications', 'variants'])
                        ->lockForUpdate()
                        ->find($item['product_id']);

                    if ($product === null || $product->trashed() || ! $product->is_active) {
                        $productName = $product?->name ?? 'Barang pilihan';

                        throw ValidationException::withMessages(['cart' => 'Barang '.$productName.' sudah tidak tersedia.']);
                    }

                    $specificationData = $this->normalizeSpecifications(
                        $product->category->specifications,
                        $item['specification_values'] ?? [],
                        [],
                        $item['specification_custom_keys'] ?? [],
                    );
                    $variant = $this->findMatchingVariant($product, $specificationData['values'], $specificationData['custom_keys']);

                    if ($product->variants->where('is_active', true)->isNotEmpty() && $variant === null && $specificationData['custom_keys'] === []) {
                        throw ValidationException::withMessages(['cart' => 'Harga variasi '.$product->name.' sudah tidak tersedia. Perbarui spesifikasi atau harga custom produk.']);
                    }

                    $pricingBasis = $product->pricing_method === 'weight' ? 'weight' : 'unit';
                    $weight = filled($item['actual_weight'] ?? null) ? (float) $item['actual_weight'] : null;
                    $quantity = $pricingBasis === 'weight'
                        ? $weight
                        : (filled($item['quantity'] ?? null) ? (float) $item['quantity'] : null);
                    $amount = $pricingBasis === 'weight' ? $weight : $quantity;

                    if ($amount === null || $amount <= 0) {
                        throw ValidationException::withMessages(['cart' => 'Jumlah atau berat '.$product->name.' tidak valid.']);
                    }

                    $unitPrice = (float) ($variant?->selling_price ?? $product->selling_price);
                    if ($this->hasConfiguredDiameterPrices($product->category)) {
                        $unitPrice = $this->dimensionPriceForSpecifications($product->category, $specificationData['values'])
                            ?? throw ValidationException::withMessages(['cart' => 'Harga ukuran '.$product->name.' tidak tersedia. Perbarui harga per kg di Atribut Kategori.']);
                    }
                    $stockQuantity = $pricingBasis === 'weight' ? $weight : $quantity;
                    $unitName = $pricingBasis === 'weight' ? 'kg' : ($product->pricing_method === 'bar' ? 'batang' : $product->unit->abbreviation);

                    $normalizedCart[] = [
                        'product' => $product,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'actual_weight' => $weight,
                        'stock_quantity' => $stockQuantity,
                        'pricing_basis' => $pricingBasis,
                        'unit_name' => $unitName,
                        'unit_price' => $unitPrice,
                        'line_total' => round((float) $amount * $unitPrice, 2),
                        'specifications' => $specificationData['labels'],
                        'notes' => $item['notes'] ?? null,
                    ];
                }

                $total = round(array_sum(array_column($normalizedCart, 'line_total')), 2);

                if ((float) $validated['amountReceived'] < $total) {
                    throw ValidationException::withMessages(['amountReceived' => 'Uang diterima belum mencukupi total transaksi.']);
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
                    $product = $item['product'];
                    $stockQuantity = $item['stock_quantity'];

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
                        'unit_name' => $item['unit_name'],
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
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

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
        session()->forget($this->cartSessionKey());
        $this->showConfirmation = false;
        $this->showProductDetails = false;
        $this->editingCartKey = null;
        session()->flash('status', 'Transaksi berhasil disimpan.');
        $this->dispatch('purchase-completed', message: 'Pembayaran berhasil! Invoice '.$sale->invoice_number.' · Total Rp '.number_format((float) $sale->total, 0, ',', '.'));
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

        $cartForDisplay = $this->cartForDisplay();
        $selectedProduct = $this->productId
            ? Product::query()->with(['brand', 'unit', 'category.specifications', 'inventoryLevels', 'variants'])->find($this->productId)
            : null;

        return view('livewire.sales.point-of-sale', [
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'dimensionSets' => $selectedProduct ? $this->dimensionSetsForCategory($selectedProduct->category) : [],
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'cartForDisplay' => $cartForDisplay,
            'subtotal' => round(array_sum(array_column($cartForDisplay, 'line_total')), 2),
            'isCartPage' => request()->routeIs('kasir.cart'),
        ]);
    }

    private function persistCart(): void
    {
        session()->put($this->cartSessionKey(), [
            'items' => $this->cart,
            'customerName' => $this->customerName,
            'amountReceived' => $this->amountReceived,
            'checkoutToken' => $this->checkoutToken,
        ]);
    }

    private function cartSessionKey(): string
    {
        return 'cashier.cart.'.auth()->id();
    }

    /** @return array<int, array<string, mixed>> */
    private function cartForDisplay(): array
    {
        $products = Product::query()->with(['variants', 'category.specifications'])->whereIn('id', collect($this->cart)->pluck('product_id'))->get()->keyBy('id');

        return array_map(function (array $item) use ($products): array {
            $product = $products->get($item['product_id']);

            if ($product === null) {
                $item['price_available'] = false;
                $item['unit_price'] = 0;
                $item['line_total'] = 0;

                return $item;
            }

            $variant = $this->findMatchingVariant($product, $item['specification_values'] ?? [], $item['specification_custom_keys'] ?? []);
            $hasActiveVariants = $product->variants->contains(fn (ProductVariant $activeVariant): bool => $activeVariant->is_active);
            $item['price_available'] = ! $hasActiveVariants || $variant !== null || ($item['specification_custom_keys'] ?? []) !== [];
            $item['unit_price'] = $item['price_available'] ? (float) ($variant?->selling_price ?? $product->selling_price) : 0;
            if ($item['price_available'] && $this->hasConfiguredDiameterPrices($product->category)) {
                $dimensionPrice = $this->dimensionPriceForSpecifications($product->category, $item['specification_values'] ?? []);
                $item['price_available'] = $dimensionPrice !== null;
                $item['unit_price'] = $dimensionPrice ?? 0;
            }
            $amount = $product->pricing_method === 'weight'
                ? (float) ($item['actual_weight'] ?? 0)
                : (float) ($item['quantity'] ?? 0);
            $item['line_total'] = round($amount * $item['unit_price'], 2);

            return $item;
        }, $this->cart);
    }

    private function findMatchingCartItemIndex(array $cartItem): ?int
    {
        foreach ($this->cart as $index => $existingItem) {
            if ($existingItem['key'] === $this->editingCartKey || $existingItem['product_id'] !== $cartItem['product_id']) {
                continue;
            }

            $existingSpecifications = collect($existingItem['specification_values'] ?? [])->sortKeys()->all();
            $newSpecifications = collect($cartItem['specification_values'] ?? [])->sortKeys()->all();
            $existingCustomKeys = collect($existingItem['specification_custom_keys'] ?? [])->sort()->values()->all();
            $newCustomKeys = collect($cartItem['specification_custom_keys'] ?? [])->sort()->values()->all();

            if ($existingSpecifications === $newSpecifications && $existingCustomKeys === $newCustomKeys) {
                return $index;
            }
        }

        return null;
    }

    private function combineCartItems(array $existingItem, array $newItem, Product $product): array
    {
        $existingItem['quantity'] = (float) $existingItem['quantity'] + (float) $newItem['quantity'];
        $existingItem['actual_weight'] = $existingItem['actual_weight'] !== null && $newItem['actual_weight'] !== null
            ? (float) $existingItem['actual_weight'] + (float) $newItem['actual_weight']
            : null;
        $existingItem['stock_quantity'] = $product->pricing_method === 'weight'
            ? (float) ($existingItem['actual_weight'] ?? 0)
            : (float) $existingItem['quantity'];
        $existingItem['unit_price'] = $newItem['unit_price'];
        $amount = $product->pricing_method === 'weight'
            ? (float) ($existingItem['actual_weight'] ?? 0)
            : (float) $existingItem['quantity'];
        $existingItem['line_total'] = round($amount * $existingItem['unit_price'], 2);
        $notes = collect([$existingItem['notes'] ?? null, $newItem['notes'] ?? null])
            ->filter()
            ->unique()
            ->implode(' · ');
        $existingItem['notes'] = $notes !== '' ? $notes : null;

        return $existingItem;
    }

    private function prepareProductForm(Product $product): void
    {
        $this->reset(['actualWeight', 'diameter', 'shape', 'length', 'width', 'customSpecification', 'orderNotes', 'sizePresetId', 'diameterPresetId']);
        $this->specificationInputs = [];
        $this->customSpecificationInputs = [];
        $this->quantity = '';
        $this->priceBasis = $product->pricing_method === 'weight' ? 'weight' : 'unit';
        $this->unitPrice = (string) $product->selling_price;
    }

    private function resetProductForm(): void
    {
        $this->reset([
            'productId', 'actualWeight', 'unitPrice', 'diameter', 'shape', 'length', 'width',
            'customSpecification', 'orderNotes', 'sizePresetId',
        ]);
        $this->specificationInputs = [];
        $this->customSpecificationInputs = [];
        $this->quantity = '';
        $this->priceBasis = 'unit';
    }

    /**
     * @return array<int, array{shape: string, length: string, width: string, diameter: string}>
     */
    private function dimensionSetsForCategory(Category $category): array
    {
        if (is_array($category->dimension_sets) && $category->dimension_sets !== []) {
            return array_map(fn (array $set): array => [...$set, 'price' => ($set['type'] ?? null) === 'size' ? null : (isset($set['price']) && is_numeric($set['price']) ? (float) $set['price'] : null)], $category->dimension_sets);
        }

        $fieldByKey = [
            'shape' => 'bentuk',
            'length' => 'panjang',
            'width' => 'lebar',
            'diameter' => 'diameter_besi',
        ];
        $specifications = $category->specifications->keyBy('key');
        $rowCount = max([0, ...array_map(
            fn (string $key): int => count($specifications->get($key)?->options ?? []),
            array_values($fieldByKey),
        )]);
        $dimensionSets = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $dimensionSet = [];

            foreach ($fieldByKey as $field => $key) {
                $dimensionSet[$field] = (string) ($specifications->get($key)?->options[$index] ?? '');
            }

            if (collect($dimensionSet)->contains(fn (string $value): bool => filled($value))) {
                $dimensionSet['price'] = null;
                $dimensionSets[] = $dimensionSet;
            }
        }

        return $dimensionSets;
    }

    /** @param array<string, string> $specifications */
    private function dimensionPriceForSpecifications(Category $category, array $specifications): ?float
    {
        $diameter = trim($specifications['diameter_besi'] ?? '');
        $dimensionSet = collect($this->dimensionSetsForCategory($category))->first(fn (array $set): bool => $diameter !== ''
            && mb_strtolower(trim((string) ($set['diameter'] ?? ''))) === mb_strtolower($diameter));

        return isset($dimensionSet['price']) && is_numeric($dimensionSet['price']) && (float) $dimensionSet['price'] > 0
            ? (float) $dimensionSet['price']
            : null;
    }

    private function hasConfiguredDiameterPrices(Category $category): bool
    {
        return collect($this->dimensionSetsForCategory($category))->contains(fn (array $set): bool => filled($set['diameter'] ?? null));
    }

    private function hasConfiguredSizeOptions(Category $category): bool
    {
        return collect($this->dimensionSetsForCategory($category))->contains(fn (array $set): bool => collect(['shape', 'length', 'width'])
            ->contains(fn (string $field): bool => filled($set[$field] ?? null)));
    }

    /**
     * @param  Collection<int, ProductSpecification>  $specifications
     * @param  array<string, string>  $selectedValues
     * @param  array<string, string>  $customValues
     * @param  array<int, string>  $forcedCustomKeys
     * @return array{values: array<string, string>, custom_keys: array<int, string>, labels: array<string, string>}
     */
    private function normalizeSpecifications(Collection $specifications, array $selectedValues, array $customValues, array $forcedCustomKeys = []): array
    {
        $values = [];
        $customKeys = [];
        $labels = [];

        foreach ($specifications as $specification) {
            $selected = $selectedValues[$specification->key] ?? '';
            $isCustom = in_array($specification->key, $forcedCustomKeys, true) || $selected === '__custom__';
            $value = $selected === '__custom__'
                ? trim($customValues[$specification->key] ?? '')
                : trim($selected);

            if ($value === '') {
                if (($specification->is_required && ! in_array($specification->key, ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true)) || in_array($specification->key, $forcedCustomKeys, true)) {
                    throw ValidationException::withMessages(['specificationInputs.'.$specification->key => 'Pilih atau isi '.$specification->name.'.']);
                }

                continue;
            }

            if (! $isCustom && filled($specification->options) && ! in_array($value, $specification->options, true)) {
                throw ValidationException::withMessages(['specificationInputs.'.$specification->key => 'Pilihan '.$specification->name.' tidak valid.']);
            }

            $values[$specification->key] = $value;
            $labels[$specification->name] = $value;

            if ($isCustom) {
                $customKeys[] = $specification->key;
            }
        }

        return ['values' => $values, 'custom_keys' => $customKeys, 'labels' => $labels];
    }

    private function findMatchingVariant(Product $product, array $values, array $customKeys): ?ProductVariant
    {
        if ($customKeys !== []) {
            return null;
        }

        $normalizedValues = collect($values)->sortKeys()->all();

        return $product->variants->first(function (ProductVariant $variant) use ($normalizedValues): bool {
            $variantSpecifications = collect($variant->specifications)->sortKeys()->all();

            return $variant->is_active
                && $variantSpecifications !== []
                && collect($normalizedValues)->only(array_keys($variantSpecifications))->sortKeys()->all() === $variantSpecifications;
        });
    }
}
