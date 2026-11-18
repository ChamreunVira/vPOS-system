<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = $this->faker->unique()->randomElement(['cash', 'khqr', 'card', 'bank_transfer', 'cheque']);
        $labels = [
            'cash' => 'Cash',
            'khqr' => 'KHQR',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
        ];

        return [
            'name' => $labels[$code] ?? ucfirst($code),
            'code' => $code,
            'is_enable' => true,
        ];
    }
}
