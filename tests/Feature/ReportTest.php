<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderItem;
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

test('user can view reports index with tabs', function (): void {
    $response = $this->actingAs($this->user)->get(route('reports.index'));

    $response->assertOk()
        ->assertSee('Reports')
        ->assertSee('Daily sales')
        ->assertSee('Revenue & profit')
        ->assertSee('Inventory')
        ->assertSee('Expenses');
});

test('reports sales tab aggregates daily orders and sales', function (): void {
    $order = Order::factory()->create([
        'order_number' => 'ORD-REP-101',
        'cashier_id' => $this->user->id,
        'status' => 'completed',
        'subtotal' => 100.00,
        'discount_amount' => 10.00,
        'total' => 90.00,
        'place_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.index', ['range' => 'today']));

    $response->assertOk();

    /** @var array<string, mixed> $reports */
    $reports = $response->viewData('reports');

    expect($reports['sales'])->toBeArray();
    $totalSales = collect($reports['sales'])->sum(2);
    $totalDiscounts = collect($reports['sales'])->sum(3);

    expect($totalSales)->toBe(90.00)
        ->and($totalDiscounts)->toBe(10.00);
});

test('reports filter by custom date range', function (): void {
    $pastOrder = Order::factory()->create([
        'order_number' => 'ORD-PAST',
        'cashier_id' => $this->user->id,
        'total' => 55.00,
        'place_at' => '2026-10-01 10:00:00',
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.index', [
        'from' => '2026-10-01',
        'to' => '2026-10-05',
    ]));

    $response->assertOk()
        ->assertSee('Oct 1');

    /** @var array<string, mixed> $reports */
    $reports = $response->viewData('reports');
    $totalSales = collect($reports['sales'])->sum(2);

    expect($totalSales)->toBe(55.00);
});

test('revenue tab calculates revenue cost of goods and profit', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'cost' => 15.00,
        'price' => 25.00,
    ]);

    $order = Order::factory()->create([
        'cashier_id' => $this->user->id,
        'total' => 50.00,
        'place_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 25.00,
        'unit_cost' => 15.00,
        'line_total' => 50.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.index', ['range' => 'today']));

    $response->assertOk();

    /** @var array<string, mixed> $reports */
    $reports = $response->viewData('reports');
    $revenueRows = collect($reports['revenue']);

    $totalRevenue = $revenueRows->sum(1);
    $totalCost = $revenueRows->sum(2);
    $totalProfit = $revenueRows->sum(3);

    expect($totalRevenue)->toBe(50.00)
        ->and($totalCost)->toBe(30.00)
        ->and($totalProfit)->toBe(20.00);
});

test('inventory tab shows real products and stock status', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Report Test Product Coffee',
        'cost' => 5.00,
        'price' => 8.00,
        'stock' => 50,
        'min_stock' => 10,
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.index'));

    $response->assertOk()
        ->assertSee('Report Test Product Coffee')
        ->assertSee('50')
        ->assertSee('$250.00'); // 50 * 5.00 = 250
});

test('expenses tab aggregates expenses by category', function (): void {
    $category = ExpenseCategory::factory()->create(['name' => 'Store Utilities']);

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'created_by' => $this->user->id,
        'amount' => 125.50,
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.index', ['range' => 'today']));

    $response->assertOk()
        ->assertSee('Store Utilities')
        ->assertSee('$125.50');
});

test('user can export reports to csv', function (): void {
    $order = Order::factory()->create([
        'cashier_id' => $this->user->id,
        'total' => 150.00,
        'place_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reports.export', ['range' => 'today']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('content-disposition'))->toContain('reports-export-');
});
