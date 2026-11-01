<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpecificationValue>
 */
class ProductSpecificationValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_specification_id' => ProductSpecification::factory(),
            'value' => fake()->word(),
        ];
    }
}
