<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
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
            'sku' => 'SKU-'.str((string) fake()->unique()->numberBetween(100000, 999999)),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Navy', 'Cream', 'Hitam', 'Abu', 'Denim']),
            'additional_price' => fake()->randomElement([0, 10000, 15000]),
            'stock' => fake()->numberBetween(5, 40),
        ];
    }

    /**
     * A SKU with no stock, used for out-of-stock labels (FR-20).
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
