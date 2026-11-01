<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ProductSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpecification>
 */
class ProductSpecificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->word(),
            'key' => fake()->unique()->word(),
            'data_type' => 'text',
            'measurement_unit' => null,
            'is_required' => false,
            'sort_order' => 0,
        ];
    }
}
