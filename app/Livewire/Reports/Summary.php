<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\InventoryLevel;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Summary extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->timezone('Asia/Jakarta')->startOfMonth()->toDateString();
        $this->dateTo = now()->timezone('Asia/Jakarta')->toDateString();
    }

    public function updatedDateFrom(): void
    {
        $this->validateOnly('dateFrom', ['dateFrom' => ['required', 'date', 'before_or_equal:dateTo']]);
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->validateOnly('dateTo', ['dateTo' => ['required', 'date', 'after_or_equal:dateFrom', 'before_or_equal:today']]);
        $this->resetPage();
    }

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function render(): View
    {
        $sales = $this->salesQuery();
        $saleItems = SaleItem::query()->whereHas('sale', fn (Builder $query) => $query
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->where('payment_status', 'paid'));
        $expenses = Expense::query()
            ->whereDate('expense_date', '>=', $this->dateFrom)
            ->whereDate('expense_date', '<=', $this->dateTo);

        return view('livewire.reports.summary', [
            'sales' => (clone $sales)->with(['user', 'items'])->latest()->paginate(15),
            'transactionCount' => (clone $sales)->count(),
            'revenue' => (float) (clone $sales)->sum('total'),
            'grossProfit' => (float) (clone $saleItems)->selectRaw('COALESCE(SUM(line_total - (quantity * unit_cost)), 0) AS aggregate')->value('aggregate'),
            'expensesTotal' => (float) (clone $expenses)->sum('amount'),
            'expenses' => (clone $expenses)->with('user')->latest('expense_date')->limit(15)->get(),
            'inventory' => InventoryLevel::query()->with('product.category', 'product.unit')
                ->where('inventory_type', 'finished')
                ->whereHas('product', fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('product_id')->get(),
            'movements' => StockMovement::query()->with(['product.unit', 'user'])
                ->whereDate('created_at', '>=', $this->dateFrom)
                ->whereDate('created_at', '<=', $this->dateTo)
                ->latest()->limit(15)->get(),
        ]);
    }

    private function salesQuery(): Builder
    {
        return Sale::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);
    }
}
