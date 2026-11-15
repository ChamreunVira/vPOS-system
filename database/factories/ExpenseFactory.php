<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'created_by' => User::factory(),
            'payment_method_id' => null,
            'description' => $this->faker->optional()->sentence(),
            'amount' => $this->faker->randomFloat(2, 5, 500),
            'expense_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }

    public function forCategory(ExpenseCategory $category): static
    {
        return $this->state(['expense_category_id' => $category->id]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }

    public function thisMonth(): static
    {
        return $this->state([
            'expense_date' => $this->faker->dateTimeBetween(
                now()->startOfMonth(),
                now()->endOfMonth()
            )->format('Y-m-d'),
        ]);
    }
}
