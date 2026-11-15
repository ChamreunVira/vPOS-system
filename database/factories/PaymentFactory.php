<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method_id' => PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Cash', 'is_enable' => true])->id,
            'amount' => 22.00,
            'refunded_amount' => 0.00,
            'reference' => 'PAY-'.fake()->bothify('####'),
            'status' => 'paid',
            'paid_at' => now(),
        ];
    }
}
