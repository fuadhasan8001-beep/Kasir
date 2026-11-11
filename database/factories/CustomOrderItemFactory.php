<?php

namespace Database\Factories;

use App\Models\CustomOrder;
use App\Models\CustomOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomOrderItem>
 */
class CustomOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_order_id' => CustomOrder::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'quantity' => fake()->randomFloat(3, 1, 20),
            'stock_basis' => 'unit',
            'stock_unit' => 'btg',
            'estimated_weight' => null,
            'actual_weight' => null,
            'price_basis' => 'fixed',
            'agreed_price' => fake()->numberBetween(10000, 500000),
            'total_price' => fake()->numberBetween(10000, 500000),
            'specifications' => null,
            'diameter' => null,
            'shape' => null,
            'notes' => null,
        ];
    }
}
