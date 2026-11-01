<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-########'),
            'barcode' => fake()->unique()->ean13(),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'product_type' => 'general',
            'pricing_method' => 'unit',
            'description' => fake()->optional()->sentence(),
            'purchase_price' => fake()->numberBetween(1000, 100000),
            'selling_price' => fake()->numberBetween(2000, 150000),
            'minimum_stock' => 0,
            'is_active' => true,
        ];
    }
}
