<?php

namespace Tests\Feature;

use App\Livewire\Categories\ManageCategories;
use App\Livewire\Products\ManageProducts;
use App\Livewire\Units\ManageUnits;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_category_specifications_and_base_unit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $specification = ProductSpecification::create([
            'category_id' => $category->id,
            'name' => 'Berat aktual',
            'key' => 'berat_aktual',
            'data_type' => 'number',
            'measurement_unit' => 'kg',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->set('sku', 'BESI-001')
            ->set('barcode', '8990000000001')
            ->set('name', 'Cakar Ayam 80 cm')
            ->set('categoryId', $category->id)
            ->set('unitId', $unit->id)
            ->set('productType', 'assembled')
            ->set('pricingMethod', 'weight')
            ->set('purchasePrice', '12000')
            ->set('sellingPrice', '15000')
            ->set('minimumStock', '0.500')
            ->set('specificationValues.'.$specification->id, '48.75')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('sku', 'BESI-001')->firstOrFail();

        $this->assertModelExists($product);
        $this->assertDatabaseHas('product_units', [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'conversion_factor' => '1.000000',
            'is_base' => 1,
        ]);
        $this->assertDatabaseHas('product_specification_values', [
            'product_id' => $product->id,
            'product_specification_id' => $specification->id,
            'value' => '48.75',
        ]);
    }

    public function test_product_cannot_be_saved_without_a_required_category_specification(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $specification = ProductSpecification::create([
            'category_id' => $category->id,
            'name' => 'Diameter besi',
            'key' => 'diameter_besi',
            'data_type' => 'number',
            'measurement_unit' => 'mm',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageProducts::class)
            ->set('sku', 'BESI-002')
            ->set('name', 'Besi rakitan')
            ->set('categoryId', $category->id)
            ->set('unitId', $unit->id)
            ->set('purchasePrice', '10000')
            ->set('sellingPrice', '15000')
            ->set('minimumStock', '0')
            ->call('save')
            ->assertHasErrors('specificationValues.'.$specification->id);

        $this->assertDatabaseMissing('products', ['sku' => 'BESI-002']);
    }

    public function test_admin_can_create_category_and_configure_a_required_specification(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin);

        Livewire::test(ManageCategories::class)
            ->set('name', 'Wiremesh')
            ->set('description', 'Lembaran besi jaring')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category = Category::where('name', 'Wiremesh')->firstOrFail();

        Livewire::test(ManageCategories::class)
            ->set('specificationCategoryId', $category->id)
            ->set('specificationName', 'Berat aktual')
            ->set('specificationKey', 'berat_aktual')
            ->set('specificationType', 'number')
            ->set('measurementUnit', 'kg')
            ->set('specificationRequired', true)
            ->call('saveSpecification')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_specifications', [
            'category_id' => $category->id,
            'key' => 'berat_aktual',
            'data_type' => 'number',
            'measurement_unit' => 'kg',
            'is_required' => 1,
        ]);
    }

    public function test_admin_can_create_unit_and_units_in_use_are_deactivated_on_delete(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin);

        Livewire::test(ManageUnits::class)
            ->set('name', 'Kilogram')
            ->set('abbreviation', 'KG')
            ->set('decimalPlaces', 3)
            ->call('save')
            ->assertHasNoErrors();

        $unit = Unit::where('abbreviation', 'kg')->firstOrFail();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

        Livewire::test(ManageUnits::class)->call('delete', $unit)->assertHasNoErrors();

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => 0]);
    }

    public function test_cashier_can_view_products_but_cannot_create_catalog_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = $this->userWithRole('kasir');

        $this->actingAs($cashier)->get(route('products.index'))->assertOk()->assertSee('Katalog barang');
        $this->actingAs($cashier)->get(route('categories.index'))->assertForbidden();

        Livewire::actingAs($cashier);
        Livewire::test(ManageProducts::class)->call('create')->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
