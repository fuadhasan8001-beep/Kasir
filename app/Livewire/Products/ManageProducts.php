<?php

namespace App\Livewire\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationValue;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ManageProducts extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $sku = '';

    public string $name = '';

    public string $brandName = '';

    public ?int $categoryId = null;

    public ?int $unitId = null;

    public string $pricingMethod = 'weight';

    public string $description = '';

    public mixed $photo = null;

    public string $currentImagePath = '';

    public bool $removeCurrentImage = false;

    public string $purchasePrice = '0';

    public string $sellingPrice = '0';

    public string $minimumStock = '0';

    public string $stockQuantity = '0';

    public bool $stockQuantityChanged = false;

    public bool $isActive = true;

    public array $specificationValues = [];

    public array $variants = [];

    public ?int $editingProductId = null;

    public ?int $detailsProductId = null;

    public bool $showForm = false;

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->specificationValues = [];
        $this->variants = [];
    }

    public function updatedStockQuantity(): void
    {
        $this->stockQuantityChanged = true;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->sku = $this->generateSku();
        $this->showForm = true;
    }

    public function edit(Product $product): void
    {
        $product->load(['specificationValues', 'variants']);

        $this->editingProductId = $product->id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->brandName = $product->brand?->name ?? '';
        $this->categoryId = $product->category_id;
        $this->unitId = Unit::query()->where('abbreviation', 'kg')->value('id');
        $this->pricingMethod = $product->pricing_method;
        $this->description = $product->description ?? '';
        $this->photo = null;
        $this->currentImagePath = $product->image_path ?? '';
        $this->removeCurrentImage = false;
        $this->purchasePrice = $product->purchase_price;
        $this->sellingPrice = $product->selling_price;
        $this->minimumStock = $this->formatQuantityForInput($product->minimum_stock);
        $this->stockQuantity = $this->formatQuantityForInput($product->inventoryLevels()->where('inventory_type', 'finished')->value('quantity') ?? 0);
        $this->stockQuantityChanged = false;
        $this->isActive = $product->is_active;
        $this->specificationValues = $product->specificationValues->pluck('value', 'product_specification_id')->all();
        $this->variants = $product->variants->map(fn (ProductVariant $variant): array => [
            'id' => $variant->id,
            'specifications' => $variant->specifications,
            'selling_price' => (string) $variant->selling_price,
            'is_active' => $variant->is_active,
        ])->all();
        $this->showForm = true;
    }

    public function addVariant(): void
    {
        $specifications = ProductSpecification::query()
            ->where('category_id', $this->categoryId)
            ->whereNotNull('options')
            ->orderBy('sort_order')
            ->get();

        if ($specifications->isEmpty()) {
            $this->addError('variants', 'Atur pilihan ukuran standar pada halaman Kategori terlebih dahulu.');

            return;
        }

        $this->variants[] = [
            'id' => null,
            'specifications' => $specifications->mapWithKeys(fn (ProductSpecification $specification): array => [
                $specification->key => $specification->options[0] ?? '',
            ])->all(),
            'selling_price' => '',
            'is_active' => true,
        ];
        $this->resetValidation('variants');
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(): void
    {

        $rules = [
            'name' => ['required', 'string', 'max:180'],
            'brandName' => ['nullable', 'string', 'max:120'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'pricingMethod' => ['required', Rule::in(['weight'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'purchasePrice' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'sellingPrice' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'minimumStock' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
            'stockQuantity' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
            'isActive' => ['boolean'],
            'specificationValues' => ['array'],
            'variants' => ['array', 'max:100'],
        ];

        $specifications = ProductSpecification::query()
            ->where('category_id', $this->categoryId)
            ->orderBy('sort_order')
            ->get();

        foreach ($specifications as $specification) {
            $rules['specificationValues.'.$specification->id] = [
                $specification->is_required ? 'required' : 'nullable',
                match ($specification->data_type) {
                    'number' => 'numeric',
                    'integer' => 'integer',
                    'boolean' => 'boolean',
                    default => 'string',
                },
                'max:500',
            ];
        }

        $variantSpecifications = $specifications->filter(fn (ProductSpecification $specification): bool => filled($specification->options));

        foreach ($this->variants as $index => $variant) {
            $rules['variants.'.$index.'.id'] = ['nullable', 'integer'];
            $rules['variants.'.$index.'.specifications'] = ['required', 'array'];
            $rules['variants.'.$index.'.selling_price'] = ['required', 'numeric', 'min:0', 'max:9999999999999.99'];
            $rules['variants.'.$index.'.is_active'] = ['required', 'boolean'];

            foreach ($variantSpecifications as $specification) {
                $rules['variants.'.$index.'.specifications.'.$specification->key] = [
                    'required', 'string', Rule::in($specification->options),
                ];
            }
        }

        $validated = $this->validate($rules, [
            'name.required' => 'Nama barang wajib diisi.',
            'categoryId.required' => 'Pilih kategori barang.',
            'purchasePrice.numeric' => 'Harga beli harus berupa angka.',
            'sellingPrice.numeric' => 'Harga jual harus berupa angka.',
            'minimumStock.numeric' => 'Stok minimum harus berupa angka.',
        ]);

        $allowedSpecificationIds = $specifications->modelKeys();
        $submittedSpecificationIds = array_map('intval', array_keys($validated['specificationValues'] ?? []));

        if (array_diff($submittedSpecificationIds, $allowedSpecificationIds) !== []) {
            throw ValidationException::withMessages(['specificationValues' => 'Spesifikasi tidak sesuai kategori yang dipilih.']);
        }

        foreach ($specifications as $specification) {
            $value = $validated['specificationValues'][$specification->id] ?? null;

            if (filled($value) && filled($specification->options) && ! $specification->allow_custom && ! in_array((string) $value, $specification->options, true)) {
                throw ValidationException::withMessages(['specificationValues.'.$specification->id => 'Pilih nilai yang tersedia untuk '.$specification->name.'.']);
            }
        }

        if ($this->variants !== [] && $variantSpecifications->isEmpty()) {
            throw ValidationException::withMessages(['variants' => 'Kategori ini belum memiliki pilihan standar untuk harga variasi.']);
        }

        $variantCombinations = [];

        foreach ($validated['variants'] ?? [] as $index => $variant) {
            $combination = collect($variant['specifications'] ?? [])
                ->only($variantSpecifications->pluck('key')->all())
                ->sortKeys()
                ->all();
            $combinationKey = json_encode($combination, JSON_THROW_ON_ERROR);

            if (count($combination) !== $variantSpecifications->count() || in_array($combinationKey, $variantCombinations, true)) {
                throw ValidationException::withMessages(['variants.'.$index.'.specifications' => 'Setiap kombinasi ukuran harus lengkap dan unik.']);
            }

            $variantCombinations[] = $combinationKey;
        }

        $previousImagePath = $this->editingProductId
            ? Product::query()->whereKey($this->editingProductId)->value('image_path')
            : null;
        $newImagePath = $this->photo?->store('products', 'public');

        DB::transaction(function () use ($validated, $specifications, $variantSpecifications, $newImagePath): void {
            $brand = filled($validated['brandName'])
                ? Brand::firstOrCreate(['name' => trim($validated['brandName'])], ['is_active' => true])
                : null;

            $attributes = [
                'name' => trim($validated['name']),
                'category_id' => $validated['categoryId'],
                'brand_id' => $brand?->id,
                'unit_id' => Unit::query()->where('abbreviation', 'kg')->value('id'),
                'pricing_method' => 'weight',
                'description' => $validated['description'] ?: null,
                'purchase_price' => $validated['purchasePrice'],
                'selling_price' => $validated['sellingPrice'],
                'minimum_stock' => $validated['minimumStock'],
                'is_active' => $validated['isActive'],
            ];

            $product = $this->editingProductId
                ? Product::query()->lockForUpdate()->findOrFail($this->editingProductId)
                : new Product;

            if ($this->editingProductId === null) {
                $product->sku = $this->generateSku();
            }

            $product->fill($attributes)->save();

            if ($newImagePath !== null) {
                $product->update(['image_path' => $newImagePath]);
            } elseif ($this->removeCurrentImage) {
                $product->update(['image_path' => null]);
            }
            $stock = InventoryLevel::firstOrCreate(
                ['product_id' => $product->id, 'inventory_type' => 'finished'],
                ['quantity' => 0],
            );

            if ($this->editingProductId === null || $this->stockQuantityChanged) {
                $stock = InventoryLevel::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $newBalance = (float) $validated['stockQuantity'];
                $stockDelta = round($newBalance - (float) $stock->quantity, 3);

                if ($newBalance < 0) {
                    throw ValidationException::withMessages(['stockQuantity' => 'Stok tidak boleh menjadi negatif.']);
                }

                if ($stockDelta !== 0.0) {
                    $stock->update(['quantity' => $newBalance]);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'movement_type' => $this->editingProductId === null ? 'in' : 'adjustment',
                        'quantity' => $stockDelta,
                        'balance_after' => $newBalance,
                        'inventory_type' => 'finished',
                        'notes' => $this->editingProductId === null ? 'Stok awal produk' : 'Penyesuaian stok melalui Data Barang',
                    ]);
                }
            }
            $product->units()->sync([$product->unit_id => ['conversion_factor' => '1.000000', 'is_base' => true]]);

            $product->specificationValues()->delete();

            foreach ($specifications as $specification) {
                $value = $validated['specificationValues'][$specification->id] ?? null;

                if ($value !== null && $value !== '') {
                    ProductSpecificationValue::create([
                        'product_id' => $product->id,
                        'product_specification_id' => $specification->id,
                        'value' => (string) $value,
                    ]);
                }
            }

            $savedVariantIds = [];

            foreach ($validated['variants'] ?? [] as $variantData) {
                $variantAttributes = [
                    'specifications' => collect($variantData['specifications'])
                        ->only($variantSpecifications->pluck('key')->all())
                        ->sortKeys()
                        ->all(),
                    'selling_price' => $variantData['selling_price'],
                    'is_active' => $variantData['is_active'],
                ];

                $variant = filled($variantData['id'] ?? null)
                    ? $product->variants()->findOrFail($variantData['id'])
                    : new ProductVariant(['product_id' => $product->id]);

                $variant->fill($variantAttributes)->save();
                $savedVariantIds[] = $variant->id;
            }

            $product->variants()->when($savedVariantIds !== [], fn ($query) => $query->whereNotIn('id', $savedVariantIds))->delete();
        });

        if (($newImagePath !== null || $this->removeCurrentImage) && $previousImagePath !== null) {
            Storage::disk('public')->delete($previousImagePath);
        }

        $this->resetForm();
        session()->flash('status', 'Barang berhasil disimpan.');
    }

    public function delete(Product $product): void
    {
        $product->delete();
        session()->flash('status', 'Barang diarsipkan dan tidak lagi muncul di katalog aktif.');
    }

    public function viewDetails(Product $product): void
    {
        $this->detailsProductId = $product->id;
    }

    public function closeDetails(): void
    {
        $this->detailsProductId = null;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function removeCurrentPhoto(): void
    {
        $this->photo = null;
        $this->currentImagePath = '';
        $this->removeCurrentImage = true;
    }

    private function resetForm(): void
    {
        $this->reset(
            'sku', 'name', 'brandName', 'categoryId', 'unitId', 'description',
            'purchasePrice', 'sellingPrice', 'minimumStock', 'specificationValues', 'variants', 'editingProductId',
            'stockQuantity', 'stockQuantityChanged',
        );
        $this->photo = null;
        $this->currentImagePath = '';
        $this->removeCurrentImage = false;
        $this->pricingMethod = 'weight';
        $this->purchasePrice = '0';
        $this->sellingPrice = '0';
        $this->minimumStock = '0';
        $this->stockQuantity = '0';
        $this->stockQuantityChanged = false;
        $this->isActive = true;
        $this->showForm = false;
        $this->resetValidation();
    }

    private function formatQuantityForInput(string|int|float $quantity): string
    {
        return str_contains((string) $quantity, '.')
            ? rtrim(rtrim((string) $quantity, '0'), '.')
            : (string) $quantity;
    }

    private function generateSku(): string
    {
        $lastSequence = Product::withTrashed()
            ->where('sku', 'like', 'BRG-%')
            ->pluck('sku')
            ->map(fn (string $sku): int => preg_match('/^BRG-(\d+)$/', $sku, $matches) === 1 ? (int) $matches[1] : 0)
            ->max() ?? 0;

        return 'BRG-'.str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    public function render(): View
    {
        return view('livewire.products.manage-products', [
            'products' => Product::query()
                ->with(['category', 'brand', 'unit', 'inventoryLevels', 'specificationValues.specification'])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%')))
                ->when($this->categoryFilter, fn ($query) => $query->where('category_id', $this->categoryFilter))
                ->orderBy('name')
                ->paginate(12),
            'categories' => Category::query()
                ->where(fn ($query) => $query->where('is_active', true)->when($this->categoryId, fn ($query) => $query->orWhereKey($this->categoryId)))
                ->orderBy('name')
                ->get(),
            'units' => Unit::query()
                ->where(fn ($query) => $query->where('is_active', true)->when($this->unitId, fn ($query) => $query->orWhereKey($this->unitId)))
                ->orderBy('name')
                ->get(),
            'specifications' => $this->categoryId
                ? ProductSpecification::where('category_id', $this->categoryId)->orderBy('sort_order')->get()
                : collect(),
            'variantSpecifications' => $this->categoryId
                ? ProductSpecification::where('category_id', $this->categoryId)->whereNotNull('options')->orderBy('sort_order')->get()
                : collect(),
            'detailsProduct' => $this->detailsProductId
                ? Product::query()->with(['category', 'brand', 'unit', 'specificationValues.specification', 'inventoryLevels'])->find($this->detailsProductId)
                : null,
            'detailMovements' => $this->detailsProductId
                ? StockMovement::query()->with('user')->where('product_id', $this->detailsProductId)->latest()->limit(8)->get()
                : collect(),
        ]);
    }
}
