<?php

namespace Database\Factories;

use App\Models\CustomOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomOrder>
 */
class CustomOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => fake()->unique()->bothify('ORD-########'),
            'request_key' => fake()->uuid(),
            'created_by' => User::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->optional()->phoneNumber(),
            'order_date' => today(),
            'specifications' => null,
            'diameter' => null,
            'shape' => null,
            'quantity' => 1,
            'stock_basis' => 'unit',
            'stock_unit' => 'btg',
            'estimated_weight' => null,
            'actual_weight' => null,
            'price_basis' => 'fixed',
            'agreed_price' => 0,
            'total_price' => 0,
            'estimated_completion_date' => null,
            'notes' => null,
            'status' => 'pending',
        ];
    }
}
