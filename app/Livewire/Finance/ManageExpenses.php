<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ManageExpenses extends Component
{
    use WithPagination;

    public string $category = '';

    public string $description = '';

    public string $amount = '';

    public string $expenseDate = '';

    public function mount(): void
    {
        $this->expenseDate = now()->timezone('Asia/Jakarta')->toDateString();
    }

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'expenseDate' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'category.required' => 'Pilih atau isi kategori pengeluaran.',
            'description.required' => 'Keterangan wajib diisi.',
            'amount.gt' => 'Jumlah pengeluaran harus lebih dari nol.',
        ]);

        Expense::create([
            'user_id' => auth()->id(),
            'category' => trim($validated['category']),
            'description' => trim($validated['description']),
            'amount' => $validated['amount'],
            'expense_date' => $validated['expenseDate'],
        ]);

        $this->reset(['category', 'description', 'amount']);
        $this->expenseDate = now()->timezone('Asia/Jakarta')->toDateString();
        session()->flash('status', 'Pengeluaran berhasil dicatat.');
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
        session()->flash('status', 'Catatan pengeluaran dihapus.');
    }

    public function render(): View
    {
        return view('livewire.finance.manage-expenses', [
            'expenses' => Expense::query()->with('user')->latest('expense_date')->latest('id')->paginate(15),
            'categories' => Expense::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'totalThisMonth' => Expense::query()->whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
        ]);
    }
}
