<?php

namespace Tests\Feature;

use App\Livewire\Inventory\ManageInventory;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryStockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_out_and_adjustment_keep_balances_and_history_in_sync(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['minimum_stock' => 5]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 100]);

        Livewire::actingAs($user);

        $component = Livewire::test(ManageInventory::class)
            ->set('productId', $product->id)
            ->set('movementType', 'in')
            ->set('quantity', '50')
            ->set('notes', 'Penerimaan stok')
            ->call('saveMovement')
            ->assertHasNoErrors()
            ->set('productId', $product->id)
            ->set('movementType', 'out')
            ->set('quantity', '10')
            ->set('notes', 'Barang rusak')
            ->call('saveMovement')
            ->assertHasNoErrors()
            ->set('productId', $product->id)
            ->set('movementType', 'adjustment')
            ->set('quantity', '-5')
            ->set('notes', 'Hasil stok opname')
            ->call('saveMovement')
            ->assertHasNoErrors()
            ->assertSee('Stok sebelum')
            ->assertSee('140')
            ->assertSee('135');

        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 135]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'movement_type' => 'in', 'quantity' => 50, 'balance_after' => 150, 'user_id' => $user->id]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'movement_type' => 'out', 'quantity' => -10, 'balance_after' => 140]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'movement_type' => 'adjustment', 'quantity' => -5, 'balance_after' => 135]);
        $this->assertDatabaseCount('stock_movements', 3);
    }

    public function test_stock_out_cannot_make_the_balance_negative(): void
    {
        $product = Product::factory()->create();
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 2]);
        Livewire::actingAs(User::factory()->create());

        Livewire::test(ManageInventory::class)
            ->set('productId', $product->id)
            ->set('movementType', 'out')
            ->set('quantity', '3')
            ->set('notes', 'Pengujian stok keluar')
            ->call('saveMovement')
            ->assertHasErrors('quantity');

        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_stock_status_distinguishes_empty_low_and_available_products(): void
    {
        $emptyProduct = Product::factory()->create(['name' => 'Stok Nol', 'minimum_stock' => 2]);
        $lowProduct = Product::factory()->create(['name' => 'Stok Rendah', 'minimum_stock' => 5]);
        $availableProduct = Product::factory()->create(['name' => 'Stok Aman', 'minimum_stock' => 5]);
        InventoryLevel::create(['product_id' => $emptyProduct->id, 'inventory_type' => 'finished', 'quantity' => 0]);
        InventoryLevel::create(['product_id' => $lowProduct->id, 'inventory_type' => 'finished', 'quantity' => 5]);
        InventoryLevel::create(['product_id' => $availableProduct->id, 'inventory_type' => 'finished', 'quantity' => 6]);
        Livewire::actingAs(User::factory()->create());

        Livewire::test(ManageInventory::class)
            ->assertSee('Habis')
            ->assertSee('Stok menipis')
            ->assertSee('Tersedia');
    }
}
