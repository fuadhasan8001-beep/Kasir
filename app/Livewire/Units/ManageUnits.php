<?php

namespace App\Livewire\Units;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ManageUnits extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $abbreviation = '';

    public int $decimalPlaces = 0;

    public ?int $editingUnitId = null;

    public function boot(): void
    {
        $this->requireCatalogManagePermission();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(Unit $unit): void
    {
        $this->requireCatalogManagePermission();
        $this->editingUnitId = $unit->id;
        $this->name = $unit->name;
        $this->abbreviation = $unit->abbreviation;
        $this->decimalPlaces = $unit->decimal_places;
    }

    public function save(): void
    {
        $this->requireCatalogManagePermission();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('units', 'name')->ignore($this->editingUnitId)],
            'abbreviation' => ['required', 'string', 'max:16', Rule::unique('units', 'abbreviation')->ignore($this->editingUnitId)],
            'decimalPlaces' => ['required', 'integer', 'between:0,6'],
        ], [
            'name.required' => 'Nama satuan wajib diisi.',
            'abbreviation.required' => 'Singkatan satuan wajib diisi.',
            'decimalPlaces.between' => 'Jumlah desimal harus antara 0 sampai 6.',
        ]);

        Unit::updateOrCreate(
            ['id' => $this->editingUnitId],
            [
                'name' => $validated['name'],
                'abbreviation' => strtolower($validated['abbreviation']),
                'decimal_places' => $validated['decimalPlaces'],
                'is_active' => true,
            ],
        );

        $this->resetForm();
        session()->flash('status', 'Satuan berhasil disimpan.');
    }

    public function delete(Unit $unit): void
    {
        $this->requireCatalogManagePermission();

        if (Product::withTrashed()->where('unit_id', $unit->id)->exists() || $unit->productUnits()->exists()) {
            $unit->update(['is_active' => false]);
            session()->flash('status', 'Satuan dipakai produk sehingga dinonaktifkan, bukan dihapus.');

            return;
        }

        $unit->delete();
        session()->flash('status', 'Satuan berhasil dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function requireCatalogManagePermission(): void
    {
        abort_unless(auth()->user()?->hasPermission('catalog.manage'), 403);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'abbreviation', 'decimalPlaces', 'editingUnitId');
        $this->decimalPlaces = 0;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.units.manage-units', [
            'units' => Unit::query()
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('abbreviation', 'like', '%'.$this->search.'%')))
                ->withCount('products')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
