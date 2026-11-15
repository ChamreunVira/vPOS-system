<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create(['name' => 'Chantha S']);
    $this->paymentMethod = PaymentMethod::firstOrCreate(
        ['code' => 'cash'],
        ['name' => 'Cash', 'is_enable' => true]
    );
});

test('authenticated user can view realtime dashboard', function (): void {
    $category = Category::factory()->create(['name' => 'Cold Drinks']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Iced Americano',
        'cost' => '1.00',
        'price' => '2.00',
        'stock' => 50,
        'min_stock' => 10,
    ]);

    $order = Order::factory()->create([
        'order_number' => 'INV-DASH-01',
        'cashier_id' => $this->user->id,
        'status' => 'completed',
        'subtotal' => 4.00,
        'total' => 4.40,
        'place_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 2.00,
        'unit_cost' => 1.00,
        'line_total' => 4.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
        'amount' => 4.40,
    ]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Good day, Chantha.')
        ->assertSee('Today\'s sales')
        ->assertSee('Total revenue')
        ->assertSee('INV-DASH-01')
        ->assertSee('Iced Americano');

    expect($response->viewData('stats')['todays_sales'])->toBe(4.4);
    expect($response->viewData('totalProducts'))->toBeGreaterThanOrEqual(1);
    expect($response->viewData('charts'))->toHaveKeys(['today', 'week', 'month']);
});

test('dashboard displays low stock products requiring attention', function (): void {
    $lowProduct = Product::factory()->create([
        'name' => 'Nearly Out Item',
        'stock' => 2,
        'min_stock' => 5,
    ]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Nearly Out Item');

    expect($response->viewData('lowStockProducts')->pluck('name')->all())
        ->toContain('Nearly Out Item');
});
