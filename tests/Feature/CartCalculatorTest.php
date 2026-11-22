<?php

use App\Helper\CartCalculator;
use Illuminate\Validation\ValidationException;

test('it calculates line total correctly', function () {
    expect(CartCalculator::lineTotal(12.50, 3))->toBe(37.50)
        ->and(CartCalculator::lineTotal(10.00, 1.5))->toBe(15.00);
});

test('it calculates discount amount correctly', function () {
    expect(CartCalculator::discountAmount(100.00, 10))->toBe(10.00)
        ->and(CartCalculator::discountAmount(100.00, 0))->toBe(0.0)
        ->and(CartCalculator::discountAmount(49.99, 15))->toBe(7.50);
});

test('it calculates tax amount on taxable amount', function () {
    expect(CartCalculator::taxAmount(100.00, 10))->toBe(10.00)
        ->and(CartCalculator::taxAmount(90.00, 10))->toBe(9.00)
        ->and(CartCalculator::taxAmount(100.00, 0))->toBe(0.0);
});

test('it calculates total and change correctly', function () {
    $total = CartCalculator::total(100.00, 10.00, 9.00);
    expect($total)->toBe(99.00);

    expect(CartCalculator::change(100.00, 99.00))->toBe(1.00)
        ->and(CartCalculator::change(50.00, 99.00))->toBe(0.0);
});

test('it throws validation exception when product is inactive or missing', function () {
    $products = collect();

    expect(fn () => CartCalculator::process([
        ['product_id' => 999, 'quantity' => 1],
    ], $products))->toThrow(ValidationException::class);
});
