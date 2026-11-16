<?php

namespace Tests\Feature;

use App\Livewire\Products\ManageProducts;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManageProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_product_photo_from_the_product_form(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $photo = UploadedFile::fake()->image('steel-product.png');

        Livewire::actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->call('create')
            ->set('sku', 'STEEL-001')
            ->set('name', 'Besi Lonjoran')
            ->set('categoryId', Category::factory()->create()->id)
            ->set('unitId', Unit::factory()->create()->id)
            ->set('productType', 'construction_material')
            ->set('pricingMethod', 'bar')
            ->set('purchasePrice', '10000')
            ->set('sellingPrice', '15000')
            ->set('minimumStock', '2')
            ->set('stockQuantity', '12.500')
            ->set('photo', $photo)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'STEEL-001')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 12.5]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'movement_type' => 'adjustment', 'quantity' => 12.5, 'balance_after' => 12.5]);
    }

    public function test_admin_can_remove_an_existing_product_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $photoPath = UploadedFile::fake()->image('existing-product.png')->store('products', 'public');
        $product = Product::factory()->create(['image_path' => $photoPath]);

        Livewire::actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->call('edit', $product)
            ->call('removeCurrentPhoto')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_path' => null,
        ]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_admin_can_replace_an_existing_product_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $oldPhotoPath = UploadedFile::fake()->image('old-product.png')->store('products', 'public');
        $product = Product::factory()->create(['image_path' => $oldPhotoPath]);
        $newPhoto = UploadedFile::fake()->image('new-product.png');

        Livewire::actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->call('edit', $product)
            ->set('photo', $newPhoto)
            ->call('save')
            ->assertHasNoErrors();

        $updatedProduct = $product->fresh();

        $this->assertNotSame($oldPhotoPath, $updatedProduct->image_path);
        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($updatedProduct->image_path);
    }

    public function test_admin_stock_edit_updates_balance_and_records_adjustment(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create();
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 10]);

        Livewire::actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->call('edit', $product)
            ->set('stockQuantity', '7.5')
            ->set('minimumStock', '9')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 7.5]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'minimum_stock' => 9]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'movement_type' => 'adjustment',
            'quantity' => -2.5,
            'balance_after' => 7.5,
            'notes' => 'Penyesuaian stok melalui Data Barang',
        ]);
    }

    public function test_editing_other_product_fields_does_not_reset_a_newer_stock_balance(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create();
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 10]);
        Livewire::actingAs($admin);

        $component = Livewire::test(ManageProducts::class)->call('edit', $product);
        InventoryLevel::query()->where('product_id', $product->id)->update(['quantity' => 8]);

        $component->set('name', 'Nama diperbarui')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_levels', ['product_id' => $product->id, 'quantity' => 8]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_product_details_show_specifications_and_stock_history(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Besi Detail', 'selling_price' => 450000]);
        InventoryLevel::create(['product_id' => $product->id, 'inventory_type' => 'finished', 'quantity' => 5]);
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'movement_type' => 'in',
            'quantity' => 5,
            'balance_after' => 5,
            'inventory_type' => 'finished',
            'notes' => 'Stok awal',
        ]);
        Livewire::actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->call('viewDetails', $product)
            ->assertSee('Detail barang')
            ->assertSee('Besi Detail')
            ->assertSee('Rp 450.000')
            ->assertSee('Riwayat perubahan stok')
            ->assertSee('Stok awal');
    }
}
