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

    public string $year = '';

    public string $month = '';

    public string $search = '';

    public string $customerName = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $status = '';

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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCustomerName(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->validateOnly('dateFrom', ['dateFrom' => ['nullable', 'date']]);
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->validateOnly('dateTo', ['dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom']]);
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
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('invoice_number', 'like', '%'.$this->search.'%')->orWhere('customer_name', 'like', '%'.$this->search.'%')))
            ->when($this->customerName, fn ($query) => $query->where('customer_name', 'like', '%'.$this->customerName.'%'))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->status === 'paid', fn ($query) => $query->where('payment_status', 'paid'))
            ->when($this->status === 'unpaid', fn ($query) => $query->where('payment_status', '!=', 'paid'))
            ->when($this->year, fn ($query) => $query->whereYear('created_at', $this->year))
            ->when($this->month, fn ($query) => $query->whereMonth('created_at', $this->month));
    }
}
