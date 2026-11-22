<?php

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->cashMethod = PaymentMethod::firstOrCreate(
        ['code' => 'cash'],
        ['name' => 'Cash', 'is_enable' => true]
    );
});

test('authenticated cashier can checkout cart successfully', function () {
    $product = Product::factory()->create([
        'price' => 10.00,
        'cost' => 6.00,
        'stock' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('pos.checkout'), [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'customer_id' => null,
        'discount_percent' => 0,
        'payment_method' => 'cash',
        'amount_received' => 30.00,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'receipt' => ['number', 'placedAt', 'customer', 'lines', 'subtotal', 'total', 'method', 'received', 'change'],
            'stock',
        ]);

    expect((float) $product->fresh()->stock)->toBe(48.0);
});

test('checkout validates required fields and payment method', function () {
    $response = $this->actingAs($this->user)->postJson(route('pos.checkout'), [
        'items' => [],
        'payment_method' => 'invalid_method',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items', 'payment_method']);
});
