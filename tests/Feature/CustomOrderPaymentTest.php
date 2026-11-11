<?php

namespace Tests\Feature;

use App\Livewire\Orders\ManageCustomOrders;
use App\Models\CustomOrder;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_order_can_be_paid_when_picked_up_with_updated_weight(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['pricing_method' => 'weight']);
        InventoryLevel::create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => 0,
        ]);

        Livewire::actingAs($admin);

        $component = Livewire::test(ManageCustomOrders::class)
            ->set('customerName', 'Pelanggan Test')
            ->set('productId', $product->id)
            ->set('quantity', '1')
            ->set('estimatedWeight', '2')
            ->set('priceBasis', 'weight')
            ->set('agreedPrice', '1000')
            ->call('addOrderItem')
            ->set('customerName', 'Pelanggan Test')
            ->call('createOrder')
            ->assertHasNoErrors();

        $order = CustomOrder::query()->with('items')->firstOrFail();
        $itemId = $order->items->firstOrFail()->id;

        $component
            ->call('transitionOrderStatus', $order->id, 'producing')
            ->set('itemChanges.'.$itemId.'.actual_weight', '2')
            ->set('itemChanges.'.$itemId.'.agreed_price', '1000')
            ->call('transitionOrderStatus', $order->id, 'made')
            ->call('transitionOrderStatus', $order->id, 'ready')
            ->call('preparePayment', $order->id)
            ->set('itemChanges.'.$itemId.'.actual_weight', '2.5')
            ->set('amountReceived', '3000')
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'custom_order_id' => $order->id,
            'customer_name' => 'Pelanggan Test',
            'total' => 2500,
            'payment_method' => 'cash',
            'amount_received' => 3000,
            'change_amount' => 500,
        ]);
        $this->assertDatabaseHas('custom_orders', ['id' => $order->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 0]);
        $this->assertDatabaseHas('stock_movements', [
            'custom_order_item_id' => $itemId,
            'movement_type' => 'custom_order_weight_adjustment',
            'quantity' => 0.5,
        ]);
    }
}
