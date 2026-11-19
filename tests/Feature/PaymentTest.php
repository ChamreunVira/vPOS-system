<?php

use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->cashMethod = PaymentMethod::firstOrCreate(
        ['code' => 'cash'],
        ['name' => 'Cash', 'is_enable' => true]
    );
    $this->khqrMethod = PaymentMethod::firstOrCreate(
        ['code' => 'khqr'],
        ['name' => 'KHQR', 'is_enable' => true]
    );
    $this->cardMethod = PaymentMethod::firstOrCreate(
        ['code' => 'card'],
        ['name' => 'Card', 'is_enable' => true]
    );
});

test('user can view payments index with transactions and tabs', function (): void {
    $order = Order::factory()->create([
        'order_number' => 'ORD-1001',
        'cashier_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payment_method_id' => $this->cashMethod->id,
        'reference' => 'PAY-TEST-001',
        'amount' => 45.50,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('payments.index'));

    $response->assertOk()
        ->assertSee('Payments')
        ->assertSee('PAY-TEST-001')
        ->assertSee('ORD-1001')
        ->assertSee('$45.50')
        ->assertSee('Payment transactions')
        ->assertSee('Cash in / out')
        ->assertSee('Daily cash summary')
        ->assertSee('Payment methods');
});

test('payments can be filtered by search, status, method, and date', function (): void {
    $customerA = Customer::factory()->create(['name' => 'Alice Walker']);
    $customerB = Customer::factory()->create(['name' => 'Bob Builder']);

    $orderA = Order::factory()->create([
        'order_number' => 'ORD-FILTER-A',
        'customer_id' => $customerA->id,
        'cashier_id' => $this->user->id,
    ]);

    $orderB = Order::factory()->create([
        'order_number' => 'ORD-FILTER-B',
        'customer_id' => $customerB->id,
        'cashier_id' => $this->user->id,
    ]);

    Payment::factory()->create([
        'order_id' => $orderA->id,
        'payment_method_id' => $this->cashMethod->id,
        'reference' => 'PAY-REF-ALPHA',
        'status' => 'paid',
        'paid_at' => '2026-11-10 10:00:00',
    ]);

    Payment::factory()->create([
        'order_id' => $orderB->id,
        'payment_method_id' => $this->khqrMethod->id,
        'reference' => 'PAY-REF-BETA',
        'status' => 'refunded',
        'paid_at' => '2026-11-15 14:00:00',
    ]);

    // Search by reference
    $searchResponse = $this->actingAs($this->user)->get(route('payments.index', ['q' => 'ALPHA']));
    $searchResponse->assertOk()
        ->assertSee('PAY-REF-ALPHA')
        ->assertDontSee('PAY-REF-BETA');

    // Filter by status
    $statusResponse = $this->actingAs($this->user)->get(route('payments.index', ['status' => 'refunded']));
    $statusResponse->assertOk()
        ->assertSee('PAY-REF-BETA')
        ->assertDontSee('PAY-REF-ALPHA');

    // Filter by payment method
    $methodResponse = $this->actingAs($this->user)->get(route('payments.index', ['method' => 'khqr']));
    $methodResponse->assertOk()
        ->assertSee('PAY-REF-BETA')
        ->assertDontSee('PAY-REF-ALPHA');

    // Filter by date
    $dateResponse = $this->actingAs($this->user)->get(route('payments.index', ['date' => '2026-11-10']));
    $dateResponse->assertOk()
        ->assertSee('PAY-REF-ALPHA')
        ->assertDontSee('PAY-REF-BETA');
});

test('user can record cash in movement', function (): void {
    $response = $this->actingAs($this->user)
        ->post(route('payments.cash-movement'), [
            'type' => 'in',
            'amount' => 100.50,
            'reason' => 'Drawer float top-up',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('cash_movements', [
        'user_id' => $this->user->id,
        'type' => 'in',
        'amount' => 100.50,
        'reason' => 'Drawer float top-up',
    ]);
});

test('user can record cash out movement', function (): void {
    $response = $this->actingAs($this->user)
        ->post(route('payments.cash-movement'), [
            'type' => 'out',
            'amount' => 25.00,
            'reason' => 'Buy cleaning supplies',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('cash_movements', [
        'user_id' => $this->user->id,
        'type' => 'out',
        'amount' => 25.00,
        'reason' => 'Buy cleaning supplies',
    ]);
});

test('cash movement validation rejects invalid payload', function (): void {
    $response = $this->actingAs($this->user)
        ->post(route('payments.cash-movement'), [
            'type' => 'invalid_type',
            'amount' => -10,
            'reason' => '',
        ]);

    $response->assertSessionHasErrors(['type', 'amount', 'reason']);
});

test('daily summary calculates takings and drawer balance accurately', function (): void {
    $orderA = Order::factory()->create(['cashier_id' => $this->user->id]);
    $orderB = Order::factory()->create(['cashier_id' => $this->user->id]);
    $orderC = Order::factory()->create(['cashier_id' => $this->user->id]);

    Payment::factory()->create([
        'order_id' => $orderA->id,
        'payment_method_id' => $this->cashMethod->id,
        'amount' => 50.00,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'order_id' => $orderB->id,
        'payment_method_id' => $this->khqrMethod->id,
        'amount' => 75.00,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'order_id' => $orderC->id,
        'payment_method_id' => $this->cardMethod->id,
        'amount' => 120.00,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    CashMovement::create([
        'user_id' => $this->user->id,
        'type' => 'in',
        'amount' => 20.00,
        'reason' => 'Extra change',
    ]);

    CashMovement::create([
        'user_id' => $this->user->id,
        'type' => 'out',
        'amount' => 15.00,
        'reason' => 'Delivery tip',
    ]);

    $response = $this->actingAs($this->user)->get(route('payments.index'));

    $response->assertOk();

    /** @var array<string, float> $summary */
    $summary = $response->viewData('summary');

    expect($summary['cash_sales'])->toBe(50.00)
        ->and($summary['khqr_sales'])->toBe(75.00)
        ->and($summary['card_sales'])->toBe(120.00)
        ->and($summary['cash_in'])->toBe(20.00)
        ->and($summary['cash_out'])->toBe(15.00)
        ->and($summary['expected_in_drawer'])->toBe(55.00); // 0 + 50 + 20 - 15 = 55
});
