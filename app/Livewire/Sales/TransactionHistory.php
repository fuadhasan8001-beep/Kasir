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
        abort_unless(auth()->check(), 403);
    }

    public function toggleDetails(int $saleId): void
    {
        $this->expandedSaleId = $this->expandedSaleId === $saleId ? null : $saleId;
    }

    public function render(): View
    {
        return view('livewire.sales.transaction-history', [
            'sales' => Sale::query()
                ->with(['user', 'items'])
                ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
                ->when($this->search, fn ($query) => $query->where('invoice_number', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(15),
            'canViewProfit' => true,
        ]);
    }
}
