<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionHistory extends Component
{
    use WithPagination;

    public string $year = '';

    public string $month = '';

    public ?int $expandedSaleId = null;

    public function updatedYear(): void
    {
        $this->validateOnly('year', ['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->validateOnly('month', ['month' => ['nullable', 'integer', 'between:1,12']]);
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
                ->when($this->year, fn ($query) => $query->whereYear('created_at', $this->year))
                ->when($this->month, fn ($query) => $query->whereMonth('created_at', $this->month))
                ->latest()
                ->paginate(15),
            'canViewProfit' => true,
        ]);
    }
}
