<?php

namespace App\Livewire\Dashboard;

use App\Models\CustomOrder;
use App\Models\Expense;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
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
        $todaySales = Sale::query()->whereDate('created_at', today())->where('payment_status', 'paid');

        return view('livewire.dashboard.overview', [
            'totalProducts' => Product::query()->where('is_active', true)->count(),
            'totalStockByUnit' => InventoryLevel::query()
                ->join('products as stock_products', 'stock_products.id', '=', 'inventory_levels.product_id')
                ->join('units as stock_units', 'stock_units.id', '=', 'stock_products.unit_id')
                ->where('inventory_levels.inventory_type', 'finished')
                ->where('stock_products.is_active', true)
                ->whereNull('stock_products.deleted_at')
                ->selectRaw("CASE WHEN stock_products.pricing_method = 'weight' THEN 'kg' ELSE stock_units.abbreviation END AS abbreviation")
                ->selectRaw("CASE WHEN stock_products.pricing_method = 'weight' THEN 3 ELSE stock_units.decimal_places END AS decimal_places")
                ->selectRaw('SUM(inventory_levels.quantity) AS total_quantity')
                ->groupByRaw("CASE WHEN stock_products.pricing_method = 'weight' THEN 'kg' ELSE stock_units.abbreviation END")
                ->groupByRaw("CASE WHEN stock_products.pricing_method = 'weight' THEN 3 ELSE stock_units.decimal_places END")
                ->orderBy('abbreviation')
                ->get(),
            'todaySales' => (clone $todaySales)->count(),
            'todayRevenue' => (float) (clone $todaySales)->sum('total'),
            'lowStockCount' => InventoryLevel::query()->where('inventory_type', 'finished')->where('quantity', '>', 0)
                ->whereHas('product', fn ($query) => $query->whereColumn('products.minimum_stock', '>=', 'inventory_levels.quantity')->where('is_active', true))->count(),
            'outOfStockCount' => InventoryLevel::query()->where('inventory_type', 'finished')->where('quantity', '<=', 0)
                ->whereHas('product', fn ($query) => $query->where('is_active', true))->count(),
            'todayExpenses' => (float) Expense::query()->whereDate('expense_date', today())->sum('amount'),
            'recentSales' => Sale::query()
                ->where('payment_status', 'paid')
                ->with('user')
                ->latest()
                ->limit(6)
                ->get(),
            'lowStock' => InventoryLevel::query()
                ->with('product.unit')
                ->where('inventory_type', 'finished')
                ->where('quantity', '>', 0)
                ->whereHas('product', fn ($query) => $query->whereColumn('products.minimum_stock', '>=', 'inventory_levels.quantity')->where('is_active', true))
                ->orderBy('quantity')
                ->limit(6)
                ->get(),
            'recentMovements' => StockMovement::query()->with(['product.unit', 'user'])->latest()->limit(6)->get(),
            'recentOrders' => CustomOrder::query()->with('creator')->latest('order_date')->limit(5)->get(),
        ]);
    }
}
