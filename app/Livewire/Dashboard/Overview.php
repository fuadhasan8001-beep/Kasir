<?php

namespace App\Livewire\Dashboard;

use App\Models\Expense;
use App\Models\InventoryLevel;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Overview extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function render(): View
    {
        $todaySales = Sale::query()->whereDate('created_at', today());
        $todayItems = SaleItem::query()->whereHas('sale', fn ($query) => $query->whereDate('created_at', today()));
        $profitAvailable = ! (clone $todayItems)->where('unit_cost', '<=', 0)->exists();
        $todayExpenses = (float) Expense::query()->whereDate('expense_date', today())->sum('amount');

        return view('livewire.dashboard.overview', [
            'todaySales' => (clone $todaySales)->count(),
            'todayRevenue' => (float) (clone $todaySales)->sum('total'),
            'todayProfit' => $profitAvailable
                ? (float) (clone $todayItems)->sum(DB::raw('line_total - (COALESCE(actual_weight, quantity) * unit_cost)'))
                    - (float) (clone $todaySales)->sum('discount')
                : null,
            'todayExpenses' => $todayExpenses,
            'todayNetProfit' => $profitAvailable
                ? (float) (clone $todayItems)->sum(DB::raw('line_total - (COALESCE(actual_weight, quantity) * unit_cost)'))
                    - (float) (clone $todaySales)->sum('discount') - $todayExpenses
                : null,
            'recentSales' => Sale::query()
                ->with('user')
                ->latest()
                ->limit(6)
                ->get(),
            'lowStock' => InventoryLevel::query()
                ->with('product.unit')
                ->where('inventory_type', 'finished')
                ->whereHas('product', fn ($query) => $query->whereColumn('products.minimum_stock', '>=', 'inventory_levels.quantity')->where('is_active', true))
                ->orderBy('quantity')
                ->limit(6)
                ->get(),
            'profitAvailable' => $profitAvailable,
        ]);
    }
}
