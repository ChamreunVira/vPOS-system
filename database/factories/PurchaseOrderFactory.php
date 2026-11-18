<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'created_by' => User::factory(),
            'purchase_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => PurchaseOrderStatus::Pending->value,
            'total' => 100.00,
            'notes' => fake()->sentence(),
            'ordered_at' => now(),
            'received_at' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseOrderStatus::Received->value,
            'received_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseOrderStatus::Cancelled->value,
        ]);
    }
}
