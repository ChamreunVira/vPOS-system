<?php

namespace App\Helper;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartCalculator
{
    /**
     * Calculate line total for a given price and quantity.
     */
    public static function lineTotal(float $price, float $quantity): float
    {
        return round($price * $quantity, 2);
    }

    /**
     * Calculate discount amount from subtotal and percentage.
     */
    public static function discountAmount(float $subtotal, float $discountPercent): float
    {
        if ($discountPercent <= 0) {
            return 0.0;
        }

        return round($subtotal * ($discountPercent / 100), 2);
    }

    /**
     * Calculate tax amount from taxable amount (subtotal - discount) and tax rate.
     */
    public static function taxAmount(float $taxableAmount, float $taxRate): float
    {
        if ($taxRate <= 0 || $taxableAmount <= 0) {
            return 0.0;
        }

        return round($taxableAmount * ($taxRate / 100), 2);
    }

    /**
     * Calculate final total.
     */
    public static function total(float $subtotal, float $discountAmount, float $taxAmount): float
    {
        return round(max(0, $subtotal - $discountAmount + $taxAmount), 2);
    }

    /**
     * Calculate change to return to customer.
     */
    public static function change(float $amountReceived, float $total): float
    {
        return round(max(0, $amountReceived - $total), 2);
    }

    /**
     * Validate and process cart line items, calculating totals.
     *
     * @param  array<int, array{product_id: int, quantity: float|int}>  $items
     * @param  Collection<int, Product>|array<int, Product>  $products
     * @return array{
     *     line_items: array<int, array{product: Product, quantity: float, price: float, cost: float, line_total: float}>,
     *     subtotal: float,
     *     discount_amount: float,
     *     tax_amount: float,
     *     total: float
     * }
     *
     * @throws ValidationException
     */
    public static function process(array $items, Collection|array $products, float $discountPercent = 0.0, float $taxRate = 0.0): array
    {
        $lineItems = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            /** @var Product|null $product */
            $product = $products instanceof Collection ? $products->get($productId) : ($products[$productId] ?? null);
            $quantity = (float) $item['quantity'];

            if (! $product || $product->status !== 'active') {
                throw ValidationException::withMessages([
                    'items' => 'A product in this sale is no longer available.',
                ]);
            }

            if (round($quantity, 2) != $quantity) {
                throw ValidationException::withMessages([
                    'items' => 'Product quantities can have at most two decimal places.',
                ]);
            }

            if (! $product->unit?->allow_decimal && fmod($quantity, 1.0) !== 0.0) {
                throw ValidationException::withMessages([
                    'items' => $product->name.' must be sold in whole units.',
                ]);
            }

            if ($quantity > (float) $product->stock) {
                throw ValidationException::withMessages([
                    'items' => 'There is not enough stock for '.$product->name.'. Refresh the sale and try again.',
                ]);
            }

            $lineTotal = static::lineTotal((float) $product->price, $quantity);
            $subtotal = round($subtotal + $lineTotal, 2);

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => (float) $product->price,
                'cost' => (float) $product->cost,
                'line_total' => $lineTotal,
            ];
        }

        $discountAmount = static::discountAmount($subtotal, $discountPercent);
        $taxableAmount = max(0.0, $subtotal - $discountAmount);
        $taxAmount = static::taxAmount($taxableAmount, $taxRate);
        $total = static::total($subtotal, $discountAmount, $taxAmount);

        return [
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }
}
