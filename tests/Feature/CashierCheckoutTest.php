<?php

namespace Tests\Feature;

use App\Livewire\Sales\PointOfSale;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashierCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_card_adds_a_regular_product_to_the_cart(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        InventoryLevel::create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => 4,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->assertHasNoErrors()
            ->assertSee('product-placeholder-steel.svg')
            ->assertDontSee('Barang dipilih')
            ->assertSet('showProductDetails', true)
            ->assertCount('cart', 0)
            ->set('quantity', '1')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('showProductDetails', false)
            ->assertCount('cart', 1)
            ->assertSet('cart.0.product_id', $product->id)
            ->assertSet('cart.0.line_total', 10000.0);
    }

    public function test_cart_route_restores_the_products_selected_from_the_catalog(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);
        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('quantity', '2')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->get(route('kasir.cart'))
            ->assertOk()
            ->assertSee('Keranjang transaksi')
            ->assertSee($product->name)
            ->assertSee('2,000');
    }

    public function test_identical_products_and_specifications_are_combined_in_the_cart(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('quantity', '2')
            ->call('addItem')
            ->call('addProduct', $product->id)
            ->set('quantity', '3')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertCount('cart', 1)
            ->assertSet('cart.0.quantity', 5.0)
            ->assertSet('cart.0.line_total', 50000.0);
    }

    public function test_products_with_different_specifications_remain_separate_cart_items(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        ProductSpecification::create([
            'category_id' => $product->category_id,
            'name' => 'Diameter',
            'key' => 'diameter',
            'data_type' => 'text',
            'options' => ['Ø10 mm', 'Ø12 mm'],
            'allow_custom' => false,
            'is_required' => true,
            'sort_order' => 1,
        ]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('specificationInputs.diameter', 'Ø10 mm')
            ->set('quantity', '1')
            ->call('addItem')
            ->call('addProduct', $product->id)
            ->set('specificationInputs.diameter', 'Ø12 mm')
            ->set('quantity', '1')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertCount('cart', 2)
            ->assertSet('cart.0.specifications', ['Diameter' => 'Ø10 mm'])
            ->assertSet('cart.1.specifications', ['Diameter' => 'Ø12 mm']);
    }

    public function test_catalog_displays_the_database_price_on_the_product_card(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 12500, 'pricing_method' => 'unit']);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->assertSee($product->name)
            ->assertSee('12.500')
            ->call('addProduct', $product->id)
            ->assertSee('12.500')
            ->set('quantity', '1')
            ->assertSee('12.500');
    }

    public function test_cashier_allows_a_custom_specification_after_the_saved_size_options(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000]);
        ProductSpecification::create([
            'category_id' => $product->category_id,
            'name' => 'Ukuran',
            'key' => 'ukuran',
            'data_type' => 'text',
            'options' => ['40 cm'],
            'allow_custom' => false,
            'is_required' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'specifications' => ['ukuran' => '40 cm'],
            'selling_price' => 12000,
            'is_active' => true,
        ]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->call('selectSizePreset', '__custom__')
            ->set('customSpecificationInputs.ukuran', '55 cm')
            ->set('quantity', '1')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertCount('cart', 1)
            ->assertSet('cart.0.specification_values', ['ukuran' => '55 cm']);
    }

    public function test_custom_product_card_opens_specification_form_before_adding_to_cart(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Cakar Ayam', 'selling_price' => 25000, 'pricing_method' => 'weight']);
        $specification = ProductSpecification::create([
            'category_id' => $product->category_id,
            'name' => 'Ukuran',
            'key' => 'ukuran',
            'data_type' => 'text',
            'options' => ['40 x 40 cm'],
            'allow_custom' => true,
            'is_required' => true,
            'sort_order' => 1,
        ]);
        InventoryLevel::create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => 12,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->assertSet('showProductDetails', true)
            ->assertCount('cart', 0)
            ->set('actualWeight', '2')
            ->set('specificationInputs.ukuran', '40 x 40 cm')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('showProductDetails', false)
            ->assertCount('cart', 1)
            ->assertSet('cart.0.line_total', 50000.0)
            ->assertSet('cart.0.specifications', ['Ukuran' => '40 x 40 cm']);
    }

    public function test_editing_a_cart_item_keeps_the_original_line_until_saved(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        InventoryLevel::create([
            'product_id' => $product->id,
            'inventory_type' => 'finished',
            'quantity' => 10,
        ]);

        Livewire::actingAs($admin);

        $component = Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('quantity', '1')
            ->call('addItem')
            ->assertCount('cart', 1);
        $cartKey = $component->get('cart.0.key');

        $component->call('editItem', $cartKey)
            ->assertSet('showProductDetails', true)
            ->assertCount('cart', 1)
            ->set('quantity', '3')
            ->set('unitPrice', '12000')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('showProductDetails', false)
            ->assertCount('cart', 1)
            ->assertSet('cart.0.quantity', 3.0)
            ->assertSet('cart.0.line_total', 30000.0);
    }

    public function test_insufficient_cash_does_not_save_the_sale_or_reduce_stock(): void
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
            ->call('addProduct', $product->id)
            ->set('quantity', '1')
            ->call('addItem')
            ->call('startCheckout')
            ->set('amountReceived', '9999')
            ->call('checkout')
            ->assertHasErrors('amountReceived');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

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

        $component = Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('quantity', '2')
            ->set('unitPrice', '12000')
            ->call('addItem')
            ->assertHasNoErrors();
        $checkoutToken = $component->get('checkoutToken');
        $cart = $component->get('cart');

        $component
            ->set('cart.0.unit_price', 999999999)
            ->set('cart.0.line_total', 999999999)
            ->set('amountReceived', '25000')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('showConfirmation', false)
            ->assertSet('completedReceipt.total', 20000.0)
            ->assertSee('Cetak struk');

        $component->set('checkoutToken', $checkoutToken)->set('cart', $cart)->set('amountReceived', '25000')->call('checkout')->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'user_id' => $admin->id,
            'total' => 20000,
            'payment_method' => 'cash',
            'amount_received' => 25000,
            'change_amount' => 5000,
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
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_cashier_uses_the_database_price_for_the_selected_standard_variant(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 10000, 'pricing_method' => 'unit']);
        $specification = ProductSpecification::create([
            'category_id' => $product->category_id,
            'name' => 'Diameter',
            'key' => 'diameter',
            'data_type' => 'text',
            'options' => ['Ø10 mm', 'Ø12 mm'],
            'allow_custom' => false,
            'is_required' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'specifications' => [$specification->key => 'Ø12 mm'],
            'selling_price' => 15000,
            'is_active' => true,
        ]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);

        Livewire::actingAs($admin);

        Livewire::test(PointOfSale::class)
            ->call('addProduct', $product->id)
            ->set('specificationInputs.diameter', 'Ø12 mm')
            ->set('quantity', '2')
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSet('cart.0.unit_price', 15000.0)
            ->assertSet('cart.0.line_total', 30000.0)
            ->set('cart.0.unit_price', 1)
            ->set('cart.0.line_total', 2)
            ->set('amountReceived', '30000')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('completedReceipt.total', 30000.0);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => 15000,
            'line_total' => 30000,
        ]);
    }
}
