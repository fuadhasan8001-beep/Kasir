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

    public ?int $editingDimensionSetIndex = null;

    public int $dimensionFormKey = 0;

    /** @var array<int, array{shape: string, length: string, width: string, diameter: string}> */
    public array $storedDimensionSets = [];

    public string $specificationName = '';

    public string $specificationKey = '';

    public string $specificationType = 'text';

    public string $measurementUnit = '';

    public string $specificationOptions = '';

    /** @var array{shape: array<int, string>, length: array<int, string>, width: array<int, string>, diameter: array<int, string>, price: array<int, string>} */
    public array $dimensionOptions = [
        'shape' => [''],
        'length' => [''],
        'width' => [''],
        'diameter' => [''],
        'price' => [''],
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
        $this->editingDimensionSetIndex = null;
        $this->storedDimensionSets = $this->dimensionSetsForCategory($category);
        $this->resetDimensionOptions();
        $this->dimensionFormKey++;
        $this->resetSpecificationForm();
    }

    public function addDimensionRow(): void
    {
        if ($this->editingDimensionSetIndex !== null) {
            $this->editingDimensionSetIndex = null;
            $this->resetDimensionOptions();
            $this->dimensionFormKey++;

            return;
        }

        if (collect($this->dimensionOptions)->only(['shape', 'length', 'width'])->flatten()->every(fn (string $value): bool => blank($value))) {
            return;
        }

        if (max(array_map('count', collect($this->dimensionOptions)->only(['shape', 'length', 'width'])->all())) >= 100) {
            $this->addError('dimensionOptions', 'Maksimal 100 ukuran.');

            return;
        }

        foreach (['shape', 'length', 'width'] as $field) {
            $this->dimensionOptions[$field][] = '';
        }

        $this->resetValidation('dimensionOptions');
    }

    public function editDimensionSet(int $index): void
    {
        abort_if($this->specificationCategoryId === null, 404);

        $category = Category::query()->with('specifications')->findOrFail($this->specificationCategoryId);
        $this->storedDimensionSets = $this->dimensionSetsForCategory($category);
        abort_unless($index >= 0 && array_key_exists($index, $this->storedDimensionSets), 404);

        $this->editingDimensionSetIndex = $index;
        foreach (array_keys($this->dimensionOptions) as $field) {
            $this->dimensionOptions[$field] = [$this->storedDimensionSets[$index][$field] ?? ''];
        }
        $this->dimensionFormKey++;
        $this->resetValidation();
    }

    public function cancelDimensionSetEdit(): void
    {
        $this->editingDimensionSetIndex = null;
        $this->resetDimensionOptions();
        $this->dimensionFormKey++;
        $this->resetValidation();
    }

    public function removeDimensionRow(int $index): void
    {
        foreach (['shape', 'length', 'width'] as $field) {
            if (array_key_exists($index, $this->dimensionOptions[$field])) {
                unset($this->dimensionOptions[$field][$index]);
                $this->dimensionOptions[$field] = array_values($this->dimensionOptions[$field]);
            }

            if ($this->dimensionOptions[$field] === []) {
                $this->dimensionOptions[$field] = [''];
            }
        }

        $this->resetValidation();
    }

    public function addDiameterOption(): void
    {
        if (count($this->dimensionOptions['diameter']) >= 100) {
            $this->addError('dimensionOptions.diameter', 'Maksimal 100 pilihan diameter.');

            return;
        }
        $this->dimensionOptions['diameter'][] = '';
        $this->dimensionOptions['price'][] = '';
        $this->resetValidation(['dimensionOptions.diameter', 'dimensionOptions.price']);
    }

    public function removeDiameterOption(int $index): void
    {
        foreach (['diameter', 'price'] as $field) {
            if (array_key_exists($index, $this->dimensionOptions[$field])) {
                unset($this->dimensionOptions[$field][$index]);
                $this->dimensionOptions[$field] = array_values($this->dimensionOptions[$field]);
            }
            if ($this->dimensionOptions[$field] === []) {
                $this->dimensionOptions[$field] = [''];
            }
        }
        $this->resetValidation(['dimensionOptions.diameter', 'dimensionOptions.price']);
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
            'dimensionOptions.price' => ['required', 'array', 'max:100'],
            'dimensionOptions.price.*' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
        ]);

        $definitions = [
            'shape' => ['name' => 'Bentuk', 'key' => 'bentuk'],
            'length' => ['name' => 'Panjang', 'key' => 'panjang'],
            'width' => ['name' => 'Lebar', 'key' => 'lebar'],
            'diameter' => ['name' => 'Diameter besi', 'key' => 'diameter_besi'],
        ];
        $sizeFields = ['shape', 'length', 'width'];
        $sizeRowCount = max(array_map(fn (string $field): int => count($validated['dimensionOptions'][$field]), $sizeFields));
        $submittedDimensionSets = collect(range(0, $sizeRowCount - 1))
            ->map(fn (int $index): array => ['type' => 'size', ...collect($sizeFields)
                ->mapWithKeys(fn (string $field): array => [$field => trim($validated['dimensionOptions'][$field][$index] ?? '')])
                ->put('diameter', '')
                ->put('price', '')
                ->all()])
            ->filter(fn (array $set): bool => collect($set)->except('type')->contains(fn (string $value): bool => $value !== ''))
            ->values();

        foreach ($validated['dimensionOptions']['diameter'] as $index => $diameter) {
            if (filled($diameter) && (float) ($validated['dimensionOptions']['price'][$index] ?? 0) <= 0) {
                throw ValidationException::withMessages(['dimensionOptions.price.'.$index => 'Isi harga per kg untuk diameter ini.']);
            }
        }

        $submittedDimensionSets = $submittedDimensionSets->concat(collect($validated['dimensionOptions']['diameter'])
            ->map(fn (string $diameter, int $index): array => [
                'type' => 'diameter',
                'shape' => '',
                'length' => '',
                'width' => '',
                'diameter' => trim($diameter),
                'price' => (float) ($validated['dimensionOptions']['price'][$index] ?? 0),
            ])
            ->filter(fn (array $set): bool => $set['diameter'] !== ''))
            ->values();

        if ($submittedDimensionSets->isEmpty()) {
            $this->addError('dimensionOptions', 'Isi ukuran terlebih dahulu sebelum menyimpan.');

            return;
        }

        $category = Category::query()->with('specifications')->findOrFail($validated['specificationCategoryId']);
        $dimensionSets = collect($this->dimensionSetsForCategory($category));

        if ($this->editingDimensionSetIndex !== null) {
            if (! $dimensionSets->has($this->editingDimensionSetIndex)) {
                throw ValidationException::withMessages(['dimensionOptions' => 'Ukuran yang dipilih sudah tidak tersedia. Buka ulang daftar ukuran.']);
            }

            $dimensionSets->put($this->editingDimensionSetIndex, $submittedDimensionSets->first());
            $dimensionSets = $dimensionSets->values();
        } else {
            $dimensionSets = $dimensionSets->concat($submittedDimensionSets)->values();
        }

        if ($dimensionSets->count() > 100) {
            $this->addError('dimensionOptions', 'Maksimal 100 ukuran.');

            return;
        }

        $normalizedOptions = [];

        foreach ($definitions as $field => $definition) {
            $options = $dimensionSets
                ->map(fn (array $set): string => $set[$field])
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

        DB::transaction(function () use ($validated, $normalizedOptions, $dimensionSets): void {
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

            $category = Category::query()->findOrFail($validated['specificationCategoryId']);
            $category->dimension_sets = $dimensionSets->all();
            $category->save();
        });

        $this->storedDimensionSets = $dimensionSets->all();
        $this->resetDimensionOptions();

        $this->editingDimensionSetIndex = null;
        $this->dimensionFormKey++;

        session()->flash('status', 'Daftar bentuk dan ukuran berhasil disimpan untuk menu Kasir.');
    }

    private function resetDimensionOptions(): void
    {
        $this->dimensionOptions = [
            'shape' => [''],
            'length' => [''],
            'width' => [''],
            'diameter' => [''],
            'price' => [''],
        ];
    }

    /**
     * @return array<int, array{shape: string, length: string, width: string, diameter: string, price?: float}>
     */
    private function dimensionSetsForCategory(Category $category): array
    {
        if (is_array($category->dimension_sets) && $category->dimension_sets !== []) {
            return array_map(fn (array $set): array => [...$set, 'price' => ($set['type'] ?? null) === 'size' ? '' : (float) ($set['price'] ?? 0)], $category->dimension_sets);
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

            $dimensionSet['price'] = 0.0;

            if (collect($dimensionSet)->contains(fn (string $value): bool => filled($value))) {
                $dimensionSets[] = $dimensionSet;
            }
        }

        return $dimensionSets;
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
        $selectedCategory = $this->specificationCategoryId
            ? Category::with('specifications')->find($this->specificationCategoryId)
            : null;
        $dimensionFields = [
            'bentuk' => ['label' => 'Bentuk', 'field' => 'shape'],
            'panjang' => ['label' => 'Panjang', 'field' => 'length'],
            'lebar' => ['label' => 'Lebar', 'field' => 'width'],
        ];
        $savedDiameterOptions = collect($selectedCategory?->dimension_sets ?? [])
            ->filter(fn (array $set): bool => filled($set['diameter'] ?? null))
            ->map(fn (array $set, int $index): array => ['diameter' => $set['diameter'], 'price' => $set['price'] ?? 0, 'index' => $index])
            ->unique(fn (array $diameter): string => mb_strtolower($diameter['diameter']))
            ->values();
        $savedDimensionSets = collect($selectedCategory?->dimension_sets ?? [])
            ->map(fn (array $set): array => collect($dimensionFields)
                ->mapWithKeys(fn (array $definition, string $key): array => [
                    $key => ['label' => $definition['label'], 'value' => $set[$definition['field']] ?? null],
                ])
                ->filter(fn (array $field): bool => filled($field['value']))
                ->all())
            ->filter()
            ->values();

        if ($savedDimensionSets->isEmpty() && $selectedCategory !== null) {
            $legacyDimensionOptions = $selectedCategory->specifications->keyBy('key');
            $legacyRowCount = max([0, ...collect($dimensionFields)
                ->keys()
                ->map(fn (string $key): int => count($legacyDimensionOptions->get($key)?->options ?? []))
                ->all()]);
            $savedDimensionSets = collect(range(0, max(0, $legacyRowCount - 1)))
                ->map(fn (int $index): array => collect($dimensionFields)
                    ->mapWithKeys(fn (array $definition, string $key): array => [
                        $key => [
                            'label' => $definition['label'],
                            'value' => $legacyDimensionOptions->get($key)?->options[$index] ?? null,
                        ],
                    ])
                    ->filter(fn (array $field): bool => filled($field['value']))
                    ->all())
                ->filter()
                ->values();
            if ($savedDiameterOptions->isEmpty()) {
                $savedDiameterOptions = collect($legacyDimensionOptions->get('diameter_besi')?->options ?? [])
                    ->map(fn (string $diameter, int $index): array => ['diameter' => $diameter, 'price' => 0, 'index' => $index])
                    ->values();
            }
        }

        return view('livewire.categories.manage-categories', [
            'categories' => Category::query()
                ->withCount('products')
                ->with('specifications')
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->paginate(10),
            'selectedCategory' => $selectedCategory,
            'savedDimensionSets' => $savedDimensionSets,
            'savedDiameterOptions' => $savedDiameterOptions,
        ]);
    }
}
