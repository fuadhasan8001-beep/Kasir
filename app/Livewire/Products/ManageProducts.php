<?php

namespace App\Livewire\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationValue;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ManageProducts extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $sku = '';

    public string $barcode = '';

    public string $name = '';

    public string $brandName = '';

    public ?int $categoryId = null;

    public ?int $unitId = null;

    public string $productType = 'general';

    public string $pricingMethod = 'unit';

    public string $description = '';

    public string $purchasePrice = '0';

    public string $sellingPrice = '0';

    public string $minimumStock = '0';

    public bool $isActive = true;

    public array $specificationValues = [];

    public ?int $editingProductId = null;

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
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Product $product): void
    {
        $product->load('specificationValues');

        $this->editingProductId = $product->id;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode ?? '';
        $this->name = $product->name;
        $this->brandName = $product->brand?->name ?? '';
        $this->categoryId = $product->category_id;
        $this->unitId = $product->unit_id;
        $this->productType = $product->product_type;
        $this->pricingMethod = $product->pricing_method;
        $this->description = $product->description ?? '';
        $this->purchasePrice = $product->purchase_price;
        $this->sellingPrice = $product->selling_price;
        $this->minimumStock = $product->minimum_stock;
        $this->isActive = $product->is_active;
        $this->specificationValues = $product->specificationValues->pluck('value', 'product_specification_id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {

        $rules = [
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->editingProductId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($this->editingProductId)],
            'name' => ['required', 'string', 'max:180'],
            'brandName' => ['nullable', 'string', 'max:120'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'unitId' => ['required', 'integer', 'exists:units,id'],
            'productType' => ['required', Rule::in(['general', 'construction_material', 'assembled'])],
            'pricingMethod' => ['required', Rule::in(['unit', 'weight', 'bar'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'purchasePrice' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'sellingPrice' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'minimumStock' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
            'isActive' => ['boolean'],
            'specificationValues' => ['array'],
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

        $validated = $this->validate($rules, [
            'sku.required' => 'Kode barang wajib diisi.',
            'sku.unique' => 'Kode barang sudah digunakan.',
            'name.required' => 'Nama barang wajib diisi.',
            'categoryId.required' => 'Pilih kategori barang.',
            'unitId.required' => 'Pilih satuan dasar.',
            'purchasePrice.numeric' => 'Harga beli harus berupa angka.',
            'sellingPrice.numeric' => 'Harga jual harus berupa angka.',
            'minimumStock.numeric' => 'Stok minimum harus berupa angka.',
        ]);

        $allowedSpecificationIds = $specifications->modelKeys();
        $submittedSpecificationIds = array_map('intval', array_keys($validated['specificationValues'] ?? []));

        if (array_diff($submittedSpecificationIds, $allowedSpecificationIds) !== []) {
            throw ValidationException::withMessages(['specificationValues' => 'Spesifikasi tidak sesuai kategori yang dipilih.']);
        }

        DB::transaction(function () use ($validated, $specifications): void {
            $brand = filled($validated['brandName'])
                ? Brand::firstOrCreate(['name' => trim($validated['brandName'])], ['is_active' => true])
                : null;

            $attributes = [
                'sku' => trim($validated['sku']),
                'barcode' => filled($validated['barcode']) ? trim($validated['barcode']) : null,
                'name' => trim($validated['name']),
                'category_id' => $validated['categoryId'],
                'brand_id' => $brand?->id,
                'unit_id' => $validated['unitId'],
                'product_type' => $validated['productType'],
                'pricing_method' => $validated['pricingMethod'],
                'description' => $validated['description'] ?: null,
                'purchase_price' => $validated['purchasePrice'],
                'selling_price' => $validated['sellingPrice'],
                'minimum_stock' => $validated['minimumStock'],
                'is_active' => $validated['isActive'],
            ];

            $product = $this->editingProductId
                ? Product::query()->lockForUpdate()->findOrFail($this->editingProductId)
                : new Product;

            $product->fill($attributes)->save();
            InventoryLevel::firstOrCreate(
                ['product_id' => $product->id, 'inventory_type' => 'finished'],
                ['quantity' => 0],
            );
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
        });

        $this->resetForm();
        session()->flash('status', 'Barang berhasil disimpan.');
    }

    public function delete(Product $product): void
    {
        $product->delete();
        session()->flash('status', 'Barang diarsipkan dan tidak lagi muncul di katalog aktif.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(
            'sku', 'barcode', 'name', 'brandName', 'categoryId', 'unitId', 'description',
            'purchasePrice', 'sellingPrice', 'minimumStock', 'specificationValues', 'editingProductId',
        );
        $this->productType = 'general';
        $this->pricingMethod = 'unit';
        $this->purchasePrice = '0';
        $this->sellingPrice = '0';
        $this->minimumStock = '0';
        $this->isActive = true;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.products.manage-products', [
            'products' => Product::query()
                ->with(['category', 'brand', 'unit', 'specificationValues.specification'])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%')
                    ->orWhere('barcode', 'like', '%'.$this->search.'%')))
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
        ]);
    }
}
