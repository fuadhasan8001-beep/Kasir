<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionHistory extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    public ?int $expandedSaleId = null;

    public function updatedDateFrom(): void
    {
        $this->validateOnly('dateFrom', ['dateFrom' => ['nullable', 'date']]);
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->validateOnly('dateTo', ['dateTo' => ['nullable', 'date']]);
        $this->resetPage();
    }

    public function boot(): void
    {
        abort_unless(
            auth()->user()?->hasPermission('sales.view_all') || auth()->user()?->hasPermission('sales.create'),
            403,
        );
    }

    public function toggleDetails(int $saleId): void
    {
        $this->expandedSaleId = $this->expandedSaleId === $saleId ? null : $saleId;
    }

    public function render(): View
    {
        $canViewAll = auth()->user()?->hasPermission('sales.view_all') ?? false;

        return view('livewire.sales.transaction-history', [
            'sales' => Sale::query()
                ->with(['user', 'items'])
                ->when(! $canViewAll, fn ($query) => $query->where('user_id', auth()->id()))
                ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
                ->when($this->search, fn ($query) => $query->where('invoice_number', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(15),
            'canViewAll' => $canViewAll,
            'canViewProfit' => auth()->user()?->hasPermission('reports.view') ?? false,
        ]);
    }
}
