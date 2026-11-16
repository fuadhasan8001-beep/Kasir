<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ManageInventory extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $productId = null;

    public string $movementType = 'in';

    public string $quantity = '';

    public string $inventoryType = 'finished';

    public string $notes = '';

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function saveMovement(): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'movementType' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => [
                'required', 'numeric', 'decimal:0,3', 'not_in:0', 'max:999999999',
                'min:-999999999',
                Rule::when($this->movementType !== 'adjustment', ['gt:0']),
            ],
            'inventoryType' => ['required', Rule::in(['finished'])],
            'notes' => ['required', 'string', 'max:1000'],
        ], [
            'productId.required' => 'Pilih barang terlebih dahulu.',
            'quantity.not_in' => 'Jumlah tidak boleh nol.',
            'notes.required' => 'Alasan atau keterangan perubahan stok wajib diisi.',
        ]);

        DB::transaction(function () use ($validated): void {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['productId']);
            $stock = InventoryLevel::firstOrCreate(
                ['product_id' => $product->id, 'inventory_type' => $validated['inventoryType']],
                ['quantity' => 0],
            );
            $stock = InventoryLevel::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            $delta = match ($validated['movementType']) {
                'in' => (float) $validated['quantity'],
                'out' => -1 * (float) $validated['quantity'],
                default => (float) $validated['quantity'],
            };
            $newBalance = (float) $stock->quantity + $delta;

            if ($newBalance < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stok tidak boleh menjadi negatif.']);
            }

            $stock->update(['quantity' => $newBalance]);
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'movement_type' => $validated['movementType'],
                'quantity' => $delta,
                'balance_after' => $newBalance,
                'inventory_type' => $validated['inventoryType'],
                'notes' => $validated['notes'] ?: null,
            ]);
        });

        $this->reset(['productId', 'quantity', 'notes']);
        $this->movementType = 'in';
        $this->inventoryType = 'finished';
        $this->resetValidation();
        session()->flash('status', 'Perubahan stok berhasil dicatat.');
    }

    public function render(): View
    {
        $stockSummary = Product::query()
            ->leftJoin('inventory_levels as finished_stock', function ($join): void {
                $join->on('finished_stock.product_id', '=', 'products.id')
                    ->where('finished_stock.inventory_type', '=', 'finished');
            })
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->selectRaw('COUNT(products.id) AS product_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(finished_stock.quantity, 0) = 0 THEN 1 ELSE 0 END) AS out_of_stock_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(finished_stock.quantity, 0) > 0 AND finished_stock.quantity <= products.minimum_stock THEN 1 ELSE 0 END) AS low_stock_count')
            ->selectRaw('SUM(CASE WHEN finished_stock.quantity > products.minimum_stock THEN 1 ELSE 0 END) AS available_stock_count')
            ->first();

        return view('livewire.inventory.manage-inventory', [
            'stockSummary' => $stockSummary,
            'products' => Product::query()
                ->with(['unit', 'inventoryLevels'])
                ->where('is_active', true)
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%')))
                ->orderBy('name')
                ->paginate(15),
            'movementProducts' => Product::query()->with('unit')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit_id', 'pricing_method']),
            'movements' => StockMovement::query()->with(['product.unit', 'user'])->latest()->paginate(12),
        ]);
    }
}
