<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
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
            'user_id' => User::factory(),
            'purchase_order_id' => null,
            'order_item_id' => null,
            'type' => 'adjustment',
            'quantity' => 5,
            'balance' => 55,
            'reference' => 'ADJ-'.fake()->bothify('####'),
            'note' => fake()->sentence(),
        ];
    }
}
