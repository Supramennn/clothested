<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomElement([129000, 149000, 219000]);
        $shippingFee = fake()->randomElement([0, 20000]);

        return [
            'user_id' => User::factory()->customer(),
            'order_code' => 'TRX-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'shipping_method' => $shippingFee > 0 ? 'courier' : 'pickup',
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal + $shippingFee,
            'placed_at' => now(),
        ];
    }

    /**
     * A courier order with the flat fee.
     */
    public function courier(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_method' => 'courier',
            'shipping_fee' => 20000,
            'total' => $attributes['subtotal'] + 20000,
        ]);
    }

    /**
     * A paid order past the payment phase.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * A closed order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
