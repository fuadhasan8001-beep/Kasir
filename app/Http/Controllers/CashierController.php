<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use App\Models\CustomOrderPayment;
use App\Models\CustomOrderStatusHistory;
use App\Models\Expense;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    public function index(): View
    {
        $cashierRole = Role::query()->where('name', 'kasir')->firstOrFail();

        return view('account.cashiers.index', [
            'cashiers' => User::query()
                ->whereBelongsTo($cashierRole, 'role')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama kasir wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan pengguna lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $cashierRole = Role::query()->where('name', 'kasir')->firstOrFail();
        User::query()->create([...$validated, 'role_id' => $cashierRole->id]);

        return redirect()->route('cashiers.index')->with('status', 'Akun kasir berhasil dibuat.');
    }

    public function edit(User $cashier): View
    {
        $this->ensureCashierAccount($cashier);

        return view('account.cashiers.edit', ['cashier' => $cashier]);
    }

    public function update(Request $request, User $cashier): RedirectResponse
    {
        $this->ensureCashierAccount($cashier);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($cashier->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama kasir wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan pengguna lain.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $updates['password'] = $validated['password'];
        }

        $cashier->update($updates);

        return redirect()->route('cashiers.index')->with('status', 'Akun kasir berhasil diperbarui.');
    }

    public function destroy(User $cashier): RedirectResponse
    {
        $this->ensureCashierAccount($cashier);

        if ($this->hasRecordedActivity($cashier)) {
            throw ValidationException::withMessages([
                'cashier' => 'Akun tidak dapat dihapus karena sudah memiliki riwayat transaksi atau aktivitas.',
            ]);
        }

        $cashier->delete();

        return redirect()->route('cashiers.index')->with('status', 'Akun kasir berhasil dihapus.');
    }

    private function ensureCashierAccount(User $user): void
    {
        abort_unless($user->role?->name === 'kasir', 404);
    }

    private function hasRecordedActivity(User $cashier): bool
    {
        return Sale::query()->whereBelongsTo($cashier, 'user')->exists()
            || Expense::query()->whereBelongsTo($cashier, 'user')->exists()
            || CustomOrder::query()->whereBelongsTo($cashier, 'creator')->exists()
            || CustomOrderPayment::query()->whereBelongsTo($cashier, 'user')->exists()
            || CustomOrderStatusHistory::query()->whereBelongsTo($cashier, 'user')->exists()
            || StockMovement::query()->whereBelongsTo($cashier, 'user')->exists();
    }
}
