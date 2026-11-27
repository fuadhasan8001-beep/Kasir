<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $transactionDate = '';

    public string $status = '';

    public ?int $expandedSaleId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTransactionDate(): void
    {
        $this->validateOnly('transactionDate', ['transactionDate' => ['nullable', 'date']]);
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->validateOnly('status', ['status' => ['nullable', 'in:paid,unpaid']]);
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
            'sales' => $this->filteredSalesQuery()->with(['user', 'items'])->latest()->paginate(15),
            'canViewProfit' => true,
            'transactionCount' => $this->filteredSalesQuery()->count(),
            'totalValue' => (float) $this->filteredSalesQuery()->sum('total'),
        ]);
    }

    private function filteredSalesQuery(): Builder
    {
        return Sale::query()
            ->when($this->search, fn ($query) => $query->where('invoice_number', 'like', '%'.$this->search.'%'))
            ->when($this->transactionDate, fn ($query) => $query->whereDate('created_at', $this->transactionDate))
            ->when($this->status === 'paid', fn ($query) => $query->where('payment_status', 'paid'))
            ->when($this->status === 'unpaid', fn ($query) => $query->where('payment_status', '!=', 'paid'));
    }
}
