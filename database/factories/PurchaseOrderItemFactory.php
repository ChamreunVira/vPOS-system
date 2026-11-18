<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);
        $cost = fake()->randomFloat(2, 1, 30);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'quantity' => $quantity,
            'unit_cost' => $cost,
            'line_total' => round($quantity * $cost, 2),
            'received_quantity' => 0,
        ];
    }
}
