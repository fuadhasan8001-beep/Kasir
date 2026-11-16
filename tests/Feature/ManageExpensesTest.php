<?php

namespace Tests\Feature;

use App\Livewire\Finance\ManageExpenses;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageExpensesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_a_saved_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $user->id,
            'category' => 'Operasional',
            'description' => 'Tagihan awal',
            'amount' => 25000,
            'expense_date' => today(),
        ]);
        Livewire::actingAs($user);

        Livewire::test(ManageExpenses::class)
            ->call('edit', $expense)
            ->set('category', 'Transportasi')
            ->set('description', 'Pengantaran barang')
            ->set('amount', '35000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'user_id' => $user->id,
            'category' => 'Transportasi',
            'description' => 'Pengantaran barang',
            'amount' => 35000,
        ]);
        $this->assertDatabaseCount('expenses', 1);
    }
}
