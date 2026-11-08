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
    public function render(): View
    {
        $todaySales = Sale::query()->whereDate('created_at', today());
        $todayItems = SaleItem::query()->whereHas('sale', fn ($query) => $query->whereDate('created_at', today()));
        $canViewAll = auth()->user()?->hasPermission('sales.view_all') ?? false;
        $canViewProfit = auth()->user()?->hasPermission('reports.view') ?? false;
        $profitAvailable = ! $canViewProfit || ! (clone $todayItems)->where('unit_cost', '<=', 0)->exists();
        $todayExpenses = (float) Expense::query()->whereDate('expense_date', today())->sum('amount');

        return view('livewire.dashboard.overview', [
            'todaySales' => (clone $todaySales)->count(),
            'todayRevenue' => (float) (clone $todaySales)->sum('total'),
            'todayProfit' => $canViewProfit && $profitAvailable
                ? (float) (clone $todayItems)->sum(DB::raw('line_total - (COALESCE(actual_weight, quantity) * unit_cost)'))
                    - (float) (clone $todaySales)->sum('discount')
                : null,
            'todayExpenses' => $todayExpenses,
            'todayNetProfit' => $canViewProfit && $profitAvailable
                ? (float) (clone $todayItems)->sum(DB::raw('line_total - (COALESCE(actual_weight, quantity) * unit_cost)'))
                    - (float) (clone $todaySales)->sum('discount') - $todayExpenses
                : null,
            'recentSales' => Sale::query()
                ->with('user')
                ->when(! $canViewAll, fn ($query) => $query->where('user_id', auth()->id()))
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
            'canViewAll' => $canViewAll,
            'canViewProfit' => $canViewProfit,
            'profitAvailable' => $profitAvailable,
        ]);
    }
}
