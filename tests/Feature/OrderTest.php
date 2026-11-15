<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->paymentMethod = PaymentMethod::firstOrCreate(
        ['code' => 'cash'],
        ['name' => 'Cash', 'is_enable' => true]
    );
});

test('user can view orders index and list orders', function (): void {
    $order = Order::factory()->create([
        'order_number' => 'INV-TEST-0001',
        'cashier_id' => $this->user->id,
        'status' => 'completed',
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('orders.index'));

    $response->assertOk()
        ->assertSee('INV-TEST-0001')
        ->assertSee('Sales & orders');
});

test('orders can be filtered by search, status, and payment method', function (): void {
    $customerA = Customer::factory()->create(['name' => 'John Doe']);
    $customerB = Customer::factory()->create(['name' => 'Jane Smith']);

    $cardMethod = PaymentMethod::firstOrCreate(
        ['code' => 'card'],
        ['name' => 'Card', 'is_enable' => true]
    );

    $orderA = Order::factory()->create([
        'order_number' => 'INV-A-100',
        'customer_id' => $customerA->id,
        'status' => 'completed',
    ]);
    Payment::factory()->create([
        'order_id' => $orderA->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);

    $orderB = Order::factory()->create([
        'order_number' => 'INV-B-200',
        'customer_id' => $customerB->id,
        'status' => 'pending',
    ]);
    Payment::factory()->create([
        'order_id' => $orderB->id,
        'payment_method_id' => $cardMethod->id,
    ]);

    // Search by customer name
    $responseSearch = $this->actingAs($this->user)->get(route('orders.index', ['q' => 'John Doe']));
    expect($responseSearch->viewData('orders')->pluck('order_number')->all())
        ->toContain('INV-A-100')
        ->not->toContain('INV-B-200');

    // Filter by status
    $responseStatus = $this->actingAs($this->user)->get(route('orders.index', ['status' => 'pending']));
    expect($responseStatus->viewData('orders')->pluck('order_number')->all())
        ->toContain('INV-B-200')
        ->not->toContain('INV-A-100');

    // Filter by payment method
    $responseMethod = $this->actingAs($this->user)->get(route('orders.index', ['method' => 'card']));
    expect($responseMethod->viewData('orders')->pluck('order_number')->all())
        ->toContain('INV-B-200')
        ->not->toContain('INV-A-100');
});

test('user can view order details page and printable receipt', function (): void {
    $product = Product::factory()->create(['name' => 'Iced Latte']);

    $order = Order::factory()->create([
        'order_number' => 'INV-DETAILS-01',
        'cashier_id' => $this->user->id,
        'status' => 'completed',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 3.50,
        'line_total' => 7.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
        'amount' => 7.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('orders.show', $order));

    $response->assertOk()
        ->assertSee('INV-DETAILS-01')
        ->assertSee('Iced Latte')
        ->assertSee('Order items');

    $receiptResponse = $this->actingAs($this->user)->get(route('orders.receipt', $order));
    $receiptResponse->assertOk()
        ->assertSee('INV-DETAILS-01')
        ->assertSee('Iced Latte');
});

test('user can cancel a pending order', function (): void {
    $order = Order::factory()->create([
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->post(route('orders.cancel', $order));

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');

    $order->refresh();
    expect($order->status)->toBe('cancelled');
});

test('cannot cancel a completed order', function (): void {
    $order = Order::factory()->create([
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->user)->post(route('orders.cancel', $order));

    $response->assertSessionHasErrors('order');

    $order->refresh();
    expect($order->status)->toBe('completed');
});

test('user can process partial refund with restocking', function (): void {
    $product = Product::factory()->create(['name' => 'Green Tea', 'stock' => 10]);

    $order = Order::factory()->create([
        'order_number' => 'INV-REFUND-01',
        'status' => 'completed',
        'subtotal' => 20.00,
        'total' => 22.00,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10.00,
        'line_total' => 20.00,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
        'amount' => 22.00,
        'refunded_amount' => 0.00,
    ]);

    // Refund 1 unit out of 2
    $response = $this->actingAs($this->user)->post(route('orders.refund', $order), [
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
                'restock' => true,
            ],
        ],
        'reason' => 'Customer changed mind',
        'refund_method' => 'cash',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');

    $order->refresh();
    expect($order->status)->toBe('partially_refunded');

    $product->refresh();
    expect((float) $product->stock)->toBe(11.0); // Stock returned +1

    $payment->refresh();
    expect((float) $payment->refunded_amount)->toBe(11.0);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'order_item_id' => $orderItem->id,
        'type' => 'return',
        'quantity' => 1,
        'balance' => 11,
    ]);
});

test('full refund without restocking does not increase stock', function (): void {
    $product = Product::factory()->create(['name' => 'Expired Milk', 'stock' => 5]);

    $order = Order::factory()->create([
        'order_number' => 'INV-REFUND-02',
        'status' => 'completed',
        'subtotal' => 10.00,
        'total' => 11.00,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10.00,
        'line_total' => 10.00,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
        'amount' => 11.00,
        'refunded_amount' => 0.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('orders.refund', $order), [
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
                'restock' => false,
            ],
        ],
        'reason' => 'Expired product',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $order->refresh();
    expect($order->status)->toBe('refunded');

    $product->refresh();
    expect((float) $product->stock)->toBe(5.0); // No increase

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'order_item_id' => $orderItem->id,
        'type' => 'damage',
        'quantity' => 0,
    ]);
});

test('exporting orders streams a valid csv file', function (): void {
    $customer = Customer::factory()->create(['name' => 'Alice Cooper']);

    $order = Order::factory()->create([
        'order_number' => 'INV-CSV-001',
        'customer_id' => $customer->id,
        'subtotal' => 50.00,
        'total' => 55.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->paymentMethod->id,
        'amount' => 55.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('orders.export'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Order Number')
        ->toContain('INV-CSV-001')
        ->toContain('Alice Cooper');
});
