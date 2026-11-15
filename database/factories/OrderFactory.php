<?php

namespace Database\Factories;

use App\Models\Customer;
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
        return [
            'order_number' => 'INV-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'cashier_id' => User::factory(),
            'status' => 'completed',
            'subtotal' => 20.00,
            'discount_percent' => 0,
            'discount_amount' => 0.00,
            'tax_rate' => 10,
            'tax_amount' => 2.00,
            'total' => 22.00,
            'place_at' => now(),
        ];
    }
}
