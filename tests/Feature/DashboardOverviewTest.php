<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_product_stock_and_today_sales_totals(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'kg', 'decimal_places' => 3]);
        $product = Product::factory()->for($category)->for($unit)->create(['name' => 'Besi Beton']);

        InventoryLevel::query()->create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => '12.500',
        ]);

        Sale::query()->create([
            'invoice_number' => 'GJS-TEST-0001',
            'request_key' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subtotal' => 175000,
            'discount' => 0,
            'total' => 175000,
            'payment_method' => 'cash',
            'amount_received' => 200000,
            'change_amount' => 25000,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Produk')
            ->assertSee('Total Stok')
            ->assertSee('12,500 kg')
            ->assertSee('Transaksi Hari Ini')
            ->assertSee('Transaksi berhasil dibayar')
            ->assertSee('Pendapatan Hari Ini')
            ->assertSee('Rp 175.000');
    }

    public function test_dashboard_shows_empty_states_when_no_products_stock_or_sales_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada produk')
            ->assertSee('Belum ada stok')
            ->assertSee('Belum ada transaksi hari ini')
            ->assertSee('Belum ada transaksi.');
    }
}
