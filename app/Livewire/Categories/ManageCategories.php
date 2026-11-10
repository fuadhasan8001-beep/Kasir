<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
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

    public ?int $specificationCategoryId = null;

    public string $specificationName = '';

    public string $specificationKey = '';

    public string $specificationType = 'text';

    public string $measurementUnit = '';

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
    }

    public function editSpecification(ProductSpecification $specification): void
    {
        $this->specificationCategoryId = $specification->category_id;
        $this->editingSpecificationId = $specification->id;
        $this->specificationName = $specification->name;
        $this->specificationKey = $specification->key;
        $this->specificationType = $specification->data_type;
        $this->measurementUnit = $specification->measurement_unit ?? '';
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

        ProductSpecification::updateOrCreate(
            ['id' => $this->editingSpecificationId],
            [
                'category_id' => $validated['specificationCategoryId'],
                'name' => $validated['specificationName'],
                'key' => $validated['specificationKey'],
                'data_type' => $validated['specificationType'],
                'measurement_unit' => $validated['measurementUnit'] ?: null,
                'is_required' => $validated['specificationRequired'],
            ],
        );

        $this->resetSpecificationForm();
        session()->flash('status', 'Spesifikasi berhasil disimpan.');
    }

    public function deleteSpecification(ProductSpecification $specification): void
    {

        if ($specification->values()->exists()) {
            throw ValidationException::withMessages(['specification' => 'Spesifikasi yang sudah dipakai produk tidak dapat dihapus.']);
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
        $this->resetValidation();
    }

    private function resetSpecificationForm(): void
    {
        $this->reset('specificationName', 'specificationKey', 'specificationType', 'measurementUnit', 'specificationRequired');
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
