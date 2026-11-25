<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ManageCategories extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $description = '';

    public ?int $editingCategoryId = null;

    public bool $showCategoryForm = false;

    public ?int $specificationCategoryId = null;

    public string $specificationName = '';

    public string $specificationKey = '';

    public string $specificationType = 'text';

    public string $measurementUnit = '';

    public string $specificationOptions = '';

    /** @var array{shape: array<int, string>, length: array<int, string>, width: array<int, string>, diameter: array<int, string>} */
    public array $dimensionOptions = [
        'shape' => [''],
        'length' => [''],
        'width' => [''],
        'diameter' => [''],
    ];

    public bool $specificationAllowCustom = false;

    public bool $specificationRequired = false;

    public ?int $editingSpecificationId = null;

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function editCategory(Category $category): void
    {
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->showCategoryForm = true;
    }

    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->showCategoryForm = true;
    }

    public function saveCategory(): void
    {

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($this->editingCategoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan.',
        ]);

        $slug = Str::slug($validated['name']);

        if (Category::query()->where('slug', $slug)->when($this->editingCategoryId, fn ($query) => $query->where('id', '!=', $this->editingCategoryId))->exists()) {
            $slug .= '-'.Str::lower(Str::random(5));
        }

        Category::updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?: null,
                'is_active' => true,
            ],
        );

        $this->resetCategoryForm();
        session()->flash('status', 'Kategori berhasil disimpan.');
    }

    public function deleteCategory(Category $category): void
    {

        if (Product::withTrashed()->where('category_id', $category->id)->exists() || $category->specifications()->exists()) {
            throw ValidationException::withMessages(['category' => 'Kategori yang sudah dipakai produk atau spesifikasi tidak dapat dihapus.']);
        }

        $category->delete();
        session()->flash('status', 'Kategori berhasil dihapus.');
    }

    public function openSpecifications(Category $category): void
    {
        $this->specificationCategoryId = $category->id;
        $this->resetSpecificationForm();

        $specifications = $category->specifications()->whereIn('key', ['bentuk', 'panjang', 'lebar', 'diameter_besi'])->get()->keyBy('key');

        $this->dimensionOptions = [
            'shape' => array_values($specifications->get('bentuk')?->options ?? ['']),
            'length' => array_values($specifications->get('panjang')?->options ?? ['']),
            'width' => array_values($specifications->get('lebar')?->options ?? ['']),
            'diameter' => array_values($specifications->get('diameter_besi')?->options ?? ['']),
        ];

        foreach ($this->dimensionOptions as $field => $options) {
            if ($options === []) {
                $this->dimensionOptions[$field] = [''];
            }
        }
    }

    public function addDimensionOption(string $field): void
    {
        abort_unless(array_key_exists($field, $this->dimensionOptions), 404);

        if (count($this->dimensionOptions[$field]) >= 100) {
            $this->addError('dimensionOptions.'.$field, 'Maksimal 100 pilihan untuk setiap ukuran.');

            return;
        }

        $this->dimensionOptions[$field][] = '';
        $this->resetValidation('dimensionOptions.'.$field);
    }

    public function removeDimensionOption(string $field, int $index): void
    {
        abort_unless(array_key_exists($field, $this->dimensionOptions), 404);

        if (! array_key_exists($index, $this->dimensionOptions[$field])) {
            return;
        }

        unset($this->dimensionOptions[$field][$index]);
        $this->dimensionOptions[$field] = array_values($this->dimensionOptions[$field]);

        if ($this->dimensionOptions[$field] === []) {
            $this->dimensionOptions[$field] = [''];
        }

        $this->resetValidation('dimensionOptions.'.$field);
    }

    public function saveDimensionOptions(): void
    {
        $validated = $this->validate([
            'specificationCategoryId' => ['required', 'integer', 'exists:categories,id'],
            'dimensionOptions' => ['required', 'array'],
            'dimensionOptions.shape' => ['required', 'array', 'max:100'],
            'dimensionOptions.shape.*' => ['nullable', 'string', 'max:120'],
            'dimensionOptions.length' => ['required', 'array', 'max:100'],
            'dimensionOptions.length.*' => ['nullable', 'string', 'max:120'],
            'dimensionOptions.width' => ['required', 'array', 'max:100'],
            'dimensionOptions.width.*' => ['nullable', 'string', 'max:120'],
            'dimensionOptions.diameter' => ['required', 'array', 'max:100'],
            'dimensionOptions.diameter.*' => ['nullable', 'string', 'max:120'],
        ]);

        $definitions = [
            'shape' => ['name' => 'Bentuk', 'key' => 'bentuk'],
            'length' => ['name' => 'Panjang', 'key' => 'panjang'],
            'width' => ['name' => 'Lebar', 'key' => 'lebar'],
            'diameter' => ['name' => 'Diameter besi', 'key' => 'diameter_besi'],
        ];
        $normalizedOptions = [];

        foreach ($definitions as $field => $definition) {
            $options = collect($validated['dimensionOptions'][$field] ?? [])
                ->map(fn (?string $option): string => trim($option ?? ''))
                ->filter()
                ->unique()
                ->values();

            if ($options->count() > 100 || $options->contains(fn (string $option): bool => mb_strlen($option) > 120)) {
                throw ValidationException::withMessages([
                    'dimensionOptions.'.$field => 'Gunakan maksimal 100 pilihan dan batasi setiap pilihan hingga 120 karakter.',
                ]);
            }

            $normalizedOptions[$field] = [$definition, $options];
        }

        DB::transaction(function () use ($validated, $normalizedOptions): void {
            foreach ($normalizedOptions as $field => [$definition, $options]) {
                $specification = ProductSpecification::query()
                    ->where('category_id', $validated['specificationCategoryId'])
                    ->where('key', $definition['key'])
                    ->first();

                if ($options->isEmpty()) {
                    if ($specification === null) {
                        continue;
                    }

                    $hasVariantReference = ProductVariant::query()
                        ->whereHas('product', fn ($query) => $query->where('category_id', $validated['specificationCategoryId']))
                        ->get()
                        ->contains(fn (ProductVariant $variant): bool => array_key_exists($definition['key'], $variant->specifications ?? []));

                    if ($specification->values()->exists() || $hasVariantReference) {
                        throw ValidationException::withMessages([
                            'dimensionOptions.'.$field => 'Pilihan ini masih dipakai produk atau variasi. Ubah daftar pilihannya, jangan kosongkan.',
                        ]);
                    }

                    $specification->delete();

                    continue;
                }

                ProductSpecification::query()->updateOrCreate(
                    [
                        'category_id' => $validated['specificationCategoryId'],
                        'key' => $definition['key'],
                    ],
                    [
                        'name' => $definition['name'],
                        'data_type' => 'text',
                        'measurement_unit' => null,
                        'options' => $options->all(),
                        'allow_custom' => false,
                        'is_required' => false,
                        'sort_order' => array_search($definition['key'], ['bentuk', 'panjang', 'lebar', 'diameter_besi'], true),
                    ],
                );
            }
        });

        foreach ($definitions as $field => $definition) {
            $this->dimensionOptions[$field] = $normalizedOptions[$field][1]->all() ?: [''];
        }

        session()->flash('status', 'Daftar bentuk dan ukuran berhasil disimpan untuk menu Kasir.');
    }

    public function editSpecification(ProductSpecification $specification): void
    {
        $this->specificationCategoryId = $specification->category_id;
        $this->editingSpecificationId = $specification->id;
        $this->specificationName = $specification->name;
        $this->specificationKey = $specification->key;
        $this->specificationType = $specification->data_type;
        $this->measurementUnit = $specification->measurement_unit ?? '';
        $this->specificationOptions = implode("\n", $specification->options ?? []);
        $this->specificationAllowCustom = $specification->allow_custom;
        $this->specificationRequired = $specification->is_required;
    }

    public function saveSpecification(): void
    {
        $this->specificationKey = Str::snake($this->specificationKey ?: $this->specificationName);

        $validated = $this->validate([
            'specificationCategoryId' => ['required', 'integer', 'exists:categories,id'],
            'specificationName' => ['required', 'string', 'max:120'],
            'specificationKey' => ['required', 'alpha_dash:ascii', 'max:80'],
            'specificationType' => ['required', Rule::in(['text', 'number', 'integer', 'boolean'])],
            'measurementUnit' => ['nullable', 'string', 'max:32'],
            'specificationOptions' => ['nullable', 'string', 'max:5000'],
            'specificationAllowCustom' => ['boolean'],
            'specificationRequired' => ['boolean'],
        ], [
            'specificationName.required' => 'Nama spesifikasi wajib diisi.',
            'specificationKey.required' => 'Kode spesifikasi wajib diisi.',
        ]);

        $duplicate = ProductSpecification::query()
            ->where('category_id', $validated['specificationCategoryId'])
            ->where('key', $validated['specificationKey'])
            ->when($this->editingSpecificationId, fn ($query) => $query->where('id', '!=', $this->editingSpecificationId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['specificationKey' => 'Kode spesifikasi sudah digunakan pada kategori ini.']);
        }

        $options = collect(preg_split('/\r\n|\r|\n/', $validated['specificationOptions'] ?? '') ?: [])
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->unique()
            ->values();

        if ($options->count() > 100 || $options->contains(fn (string $option): bool => mb_strlen($option) > 120)) {
            throw ValidationException::withMessages(['specificationOptions' => 'Gunakan maksimal 100 pilihan dan batasi setiap pilihan hingga 120 karakter.']);
        }

        DB::transaction(function () use ($validated, $options): void {
            $specification = $this->editingSpecificationId
                ? ProductSpecification::query()->lockForUpdate()->findOrFail($this->editingSpecificationId)
                : new ProductSpecification;
            $previousKey = $specification->exists ? $specification->key : null;

            $specification->fill([
                'category_id' => $validated['specificationCategoryId'],
                'name' => $validated['specificationName'],
                'key' => $validated['specificationKey'],
                'data_type' => $validated['specificationType'],
                'measurement_unit' => $validated['measurementUnit'] ?: null,
                'options' => $options->isNotEmpty() ? $options->all() : null,
                'allow_custom' => $validated['specificationAllowCustom'],
                'is_required' => $validated['specificationRequired'],
            ])->save();

            if ($previousKey !== null && $previousKey !== $specification->key) {
                ProductVariant::query()
                    ->whereHas('product', fn ($query) => $query->where('category_id', $specification->category_id))
                    ->get()
                    ->each(function (ProductVariant $variant) use ($previousKey, $specification): void {
                        $variantSpecifications = $variant->specifications ?? [];

                        if (array_key_exists($previousKey, $variantSpecifications)) {
                            $variantSpecifications[$specification->key] = $variantSpecifications[$previousKey];
                            unset($variantSpecifications[$previousKey]);
                            $variant->update(['specifications' => $variantSpecifications]);
                        }
                    });
            }
        });

        $this->resetSpecificationForm();
        session()->flash('status', 'Spesifikasi berhasil disimpan.');
    }

    public function deleteSpecification(ProductSpecification $specification): void
    {

        $hasProductVariants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('category_id', $specification->category_id))
            ->exists();

        if ($specification->values()->exists() || $hasProductVariants) {
            throw ValidationException::withMessages(['specification' => 'Spesifikasi yang sudah dipakai produk atau harga variasi tidak dapat dihapus.']);
        }

        $specification->delete();
        session()->flash('status', 'Spesifikasi berhasil dihapus.');
    }

    public function cancelCategoryEdit(): void
    {
        $this->resetCategoryForm();
    }

    public function cancelSpecificationEdit(): void
    {
        $this->resetSpecificationForm();
    }

    private function resetCategoryForm(): void
    {
        $this->reset('name', 'description', 'editingCategoryId');
        $this->showCategoryForm = false;
        $this->resetValidation();
    }

    private function resetSpecificationForm(): void
    {
        $this->reset('specificationName', 'specificationKey', 'specificationType', 'measurementUnit', 'specificationOptions', 'specificationAllowCustom', 'specificationRequired');
        $this->specificationType = 'text';
        $this->editingSpecificationId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.categories.manage-categories', [
            'categories' => Category::query()
                ->withCount('products')
                ->with('specifications')
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->paginate(10),
            'selectedCategory' => $this->specificationCategoryId
                ? Category::with('specifications')->find($this->specificationCategoryId)
                : null,
        ]);
    }
}
