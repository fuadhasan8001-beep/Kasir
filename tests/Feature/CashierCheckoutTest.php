<?php

namespace Tests\Feature;

use App\Livewire\Sales\PointOfSale;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashierCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_cash_sale_and_decrements_stock(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        InventoryLevel::create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => 5,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->set('productId', $product->id)
            ->set('quantity', '2')
            ->set('priceBasis', 'unit')
            ->set('unitPrice', '12000')
            ->call('addItem')
            ->assertHasNoErrors()
            ->set('amountReceived', '25000')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showConfirmation', false);

        $this->assertDatabaseHas('sales', [
            'user_id' => $admin->id,
            'total' => 24000,
            'payment_method' => 'cash',
            'amount_received' => 25000,
            'change_amount' => 1000,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -2,
        ]);
    }
}
