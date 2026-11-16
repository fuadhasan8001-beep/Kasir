<?php

namespace Tests\Feature;

use App\Livewire\Reports\Summary;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_saved_sales_costs_and_expenses_for_the_selected_period(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $sale = Sale::create([
            'invoice_number' => 'INV-REPORT-001',
            'request_key' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subtotal' => 15000,
            'total' => 15000,
            'payment_method' => 'cash',
            'amount_received' => 20000,
            'change_amount' => 5000,
            'payment_status' => 'paid',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => $product->unit->abbreviation,
            'pricing_basis' => 'unit',
            'quantity' => 2,
            'unit_price' => 7500,
            'unit_cost' => 5000,
            'line_total' => 15000,
        ]);
        Expense::create([
            'user_id' => $user->id,
            'category' => 'Operasional',
            'description' => 'Biaya harian',
            'amount' => 2000,
            'expense_date' => today(),
        ]);

        $this->assertDatabaseHas('expenses', ['description' => 'Biaya harian', 'amount' => 2000]);
        $this->assertSame(2000.0, (float) Expense::query()->whereDate('expense_date', '>=', today()->startOfMonth()->toDateString())->whereDate('expense_date', '<=', today()->toDateString())->sum('amount'));

        Livewire::actingAs($user);

        Livewire::test(Summary::class)
            ->assertSet('dateFrom', now()->timezone('Asia/Jakarta')->startOfMonth()->toDateString())
            ->assertSet('dateTo', now()->timezone('Asia/Jakarta')->toDateString())
            ->assertSee('Laporan operasional')
            ->assertSee('INV-REPORT-001')
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 5.000')
            ->assertSee('Rp 2.000')
            ->assertSee('Rp 3.000');
    }

    public function test_reports_route_requires_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
