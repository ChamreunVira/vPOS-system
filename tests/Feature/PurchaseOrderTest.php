<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->supplier = Supplier::factory()->create(['name' => 'Mega Distributor', 'status' => 'active']);
    $this->productA = Product::factory()->create([
        'name' => 'Coca Cola 330ml',
        'cost' => 0.50,
        'stock' => 20.0,
        'status' => 'active',
    ]);
    $this->productB = Product::factory()->create([
        'name' => 'Potato Chips 50g',
        'cost' => 0.80,
        'stock' => 10.0,
        'status' => 'active',
    ]);
});

test('user can view purchase orders listing', function (): void {
    PurchaseOrder::factory()->create([
        'purchase_number' => 'PO-999888',
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);

    $response = $this->actingAs($this->user)->get(route('purchases.index'));

    $response->assertOk()
        ->assertSee('Purchase orders')
        ->assertSee('PO-999888')
        ->assertSee('Mega Distributor');
});

test('user can filter purchase orders by status and supplier', function (): void {
    $otherSupplier = Supplier::factory()->create(['name' => 'Different Vendor']);

    $poPending = PurchaseOrder::factory()->create([
        'purchase_number' => 'PO-000101',
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);

    $poReceived = PurchaseOrder::factory()->received()->create([
        'purchase_number' => 'PO-000102',
        'supplier_id' => $otherSupplier->id,
        'created_by' => $this->user->id,
    ]);

    $responsePending = $this->actingAs($this->user)->get(route('purchases.index', ['status' => 'pending']));
    $responsePending->assertOk()
        ->assertSee('PO-000101')
        ->assertDontSee('PO-000102');

    $responseSupplier = $this->actingAs($this->user)->get(route('purchases.index', ['supplier_id' => $otherSupplier->id]));
    $responseSupplier->assertOk()
        ->assertSee('PO-000102')
        ->assertDontSee('PO-000101');
});

test('user can view create purchase order form', function (): void {
    $response = $this->actingAs($this->user)->get(route('purchases.create'));

    $response->assertOk()
        ->assertSee('New purchase order')
        ->assertSee('Mega Distributor')
        ->assertSee('Coca Cola 330ml');
});

test('FR-PUR-02: user can create a purchase order with items, unit cost, and quantity', function (): void {
    $payload = [
        'supplier_id' => $this->supplier->id,
        'ordered_at' => now()->format('Y-m-d'),
        'status' => 'pending',
        'notes' => 'Urgent weekend restocking',
        'items' => [
            [
                'product_id' => $this->productA->id,
                'quantity' => 50,
                'unit_cost' => 0.45,
            ],
            [
                'product_id' => $this->productB->id,
                'quantity' => 30,
                'unit_cost' => 0.75,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post(route('purchases.store'), $payload);

    $purchaseOrder = PurchaseOrder::latest('id')->first();
    $this->assertNotNull($purchaseOrder);

    $response->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('success');

    $this->assertEquals(PurchaseOrderStatus::Pending->value, $purchaseOrder->status);
    $this->assertEquals($this->supplier->id, $purchaseOrder->supplier_id);
    $this->assertEquals($this->user->id, $purchaseOrder->created_by);
    $this->assertEquals(45.00, (float) $purchaseOrder->total); // (50 * 0.45 = 22.50) + (30 * 0.75 = 22.50) = 45.00
    $this->assertCount(2, $purchaseOrder->items);

    $this->assertDatabaseHas('purchase_order_items', [
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $this->productA->id,
        'quantity' => 50,
        'unit_cost' => 0.45,
        'line_total' => 22.50,
    ]);

    // Pending PO does NOT alter stock yet
    $this->assertEquals(20.0, (float) $this->productA->fresh()->stock);
    $this->assertEquals(10.0, (float) $this->productB->fresh()->stock);
});

test('FR-PUR-02: creating purchase order validates items requirement', function (): void {
    $response = $this->actingAs($this->user)->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [],
    ]);

    $response->assertSessionHasErrors(['items']);
});

test('FR-PUR-02: user can update an existing pending purchase order', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);
    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
        'unit_cost' => 0.50,
        'line_total' => 5.00,
    ]);

    $response = $this->actingAs($this->user)->put(route('purchases.update', $purchaseOrder), [
        'supplier_id' => $this->supplier->id,
        'ordered_at' => now()->format('Y-m-d'),
        'notes' => 'Updated order quantity',
        'items' => [
            [
                'product_id' => $this->productA->id,
                'quantity' => 25,
                'unit_cost' => 0.48,
            ],
        ],
    ]);

    $response->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('success');

    $purchaseOrder->refresh();
    $this->assertEquals(12.00, (float) $purchaseOrder->total); // 25 * 0.48 = 12.00
    $this->assertEquals(25, (float) $purchaseOrder->items->first()->quantity);
});

test('FR-PUR-03: receiving a purchase order increases stock and writes stock movements', function (): void {
    $initialStockA = (float) $this->productA->stock; // 20.0
    $initialStockB = (float) $this->productB->stock; // 10.0

    $purchaseOrder = PurchaseOrder::factory()->create([
        'purchase_number' => 'PO-000777',
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
        'total' => 50.00,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 15.0,
        'unit_cost' => 0.60,
        'line_total' => 9.00,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productB->id,
        'quantity' => 25.0,
        'unit_cost' => 0.85,
        'line_total' => 21.25,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.receive', $purchaseOrder));

    $response->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('success');

    $purchaseOrder->refresh();
    $this->assertEquals(PurchaseOrderStatus::Received->value, $purchaseOrder->status);
    $this->assertNotNull($purchaseOrder->received_at);

    // Stock should be increased
    $this->assertEquals($initialStockA + 15.0, (float) $this->productA->fresh()->stock);
    $this->assertEquals($initialStockB + 25.0, (float) $this->productB->fresh()->stock);

    // Stock movements must be recorded
    $movementA = StockMovement::where('product_id', $this->productA->id)
        ->where('purchase_order_id', $purchaseOrder->id)
        ->first();
    $this->assertNotNull($movementA);
    $this->assertEquals(StockMovementType::Purchase->value, $movementA->type);
    $this->assertEquals(15.0, (float) $movementA->quantity);
    $this->assertEquals($initialStockA + 15.0, (float) $movementA->balance);
    $this->assertEquals('PO-000777', $movementA->reference);

    $movementB = StockMovement::where('product_id', $this->productB->id)
        ->where('purchase_order_id', $purchaseOrder->id)
        ->first();
    $this->assertNotNull($movementB);
    $this->assertEquals(StockMovementType::Purchase->value, $movementB->type);
    $this->assertEquals(25.0, (float) $movementB->quantity);
    $this->assertEquals($initialStockB + 25.0, (float) $movementB->balance);

    // Check received_quantity on items
    $this->assertEquals(15.0, (float) $purchaseOrder->items()->where('product_id', $this->productA->id)->value('received_quantity'));
    $this->assertEquals(25.0, (float) $purchaseOrder->items()->where('product_id', $this->productB->id)->value('received_quantity'));
});

test('FR-PUR-04: optionally update product cost on receive when update_cost is true', function (): void {
    $this->assertEquals(0.50, (float) $this->productA->cost);

    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10.0,
        'unit_cost' => 0.65, // New cost
        'line_total' => 6.50,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.receive', $purchaseOrder), [
        'update_cost' => 1,
    ]);

    $response->assertRedirect(route('purchases.show', $purchaseOrder));

    // Product cost should be updated to 0.65
    $this->assertEquals(0.65, (float) $this->productA->fresh()->cost);
});

test('FR-PUR-04: optionally preserve product cost on receive when update_cost is false', function (): void {
    $this->assertEquals(0.50, (float) $this->productA->cost);

    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10.0,
        'unit_cost' => 0.65,
        'line_total' => 6.50,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.receive', $purchaseOrder), [
        'update_cost' => 0,
    ]);

    $response->assertRedirect(route('purchases.show', $purchaseOrder));

    // Product cost should remain 0.50
    $this->assertEquals(0.50, (float) $this->productA->fresh()->cost);

    // But stock was still increased
    $this->assertEquals(30.0, (float) $this->productA->fresh()->stock);
});

test('FR-PUR-03 & FR-PUR-04: creating a purchase order with status received immediately updates stock and cost', function (): void {
    $initialStock = (float) $this->productA->stock;

    $payload = [
        'supplier_id' => $this->supplier->id,
        'ordered_at' => now()->format('Y-m-d'),
        'status' => 'received',
        'update_cost' => 1,
        'items' => [
            [
                'product_id' => $this->productA->id,
                'quantity' => 40,
                'unit_cost' => 0.42,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post(route('purchases.store'), $payload);

    $po = PurchaseOrder::latest('id')->first();
    $response->assertRedirect(route('purchases.show', $po));

    $this->assertEquals(PurchaseOrderStatus::Received->value, $po->status);
    $this->assertEquals($initialStock + 40, (float) $this->productA->fresh()->stock);
    $this->assertEquals(0.42, (float) $this->productA->fresh()->cost);
});

test('FR-PUR-05: user can cancel a pending purchase order', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'purchase_number' => 'PO-000888',
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
        'status' => PurchaseOrderStatus::Pending->value,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
        'unit_cost' => 0.50,
        'line_total' => 5.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.cancel', $purchaseOrder));

    $response->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('success');

    $purchaseOrder->refresh();
    $this->assertEquals(PurchaseOrderStatus::Cancelled->value, $purchaseOrder->status);
    $this->assertTrue($purchaseOrder->isCancelled());

    // Product stock has not changed
    $this->assertEquals(20.0, (float) $this->productA->fresh()->stock);
});

test('FR-PUR-05: cannot cancel a purchase order that is already received', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->received()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.cancel', $purchaseOrder));

    $response->assertSessionHasErrors(['status']);
    $this->assertEquals(PurchaseOrderStatus::Received->value, $purchaseOrder->fresh()->status);
});

test('cannot receive a purchase order that is already cancelled', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->cancelled()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
        'unit_cost' => 0.50,
        'line_total' => 5.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('purchases.receive', $purchaseOrder));

    $response->assertSessionHasErrors(['status']);
    $this->assertEquals(PurchaseOrderStatus::Cancelled->value, $purchaseOrder->fresh()->status);
    $this->assertEquals(20.0, (float) $this->productA->fresh()->stock);
});

test('cannot edit a purchase order that is already received', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->received()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('purchases.edit', $purchaseOrder));
    $response->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('error');

    $updateResponse = $this->actingAs($this->user)->put(route('purchases.update', $purchaseOrder), [
        'supplier_id' => $this->supplier->id,
        'items' => [
            ['product_id' => $this->productA->id, 'quantity' => 10, 'unit_cost' => 1.0],
        ],
    ]);
    $updateResponse->assertRedirect(route('purchases.show', $purchaseOrder))
        ->assertSessionHas('error');
});

test('user can export purchase orders to CSV', function (): void {
    PurchaseOrder::factory()->create([
        'purchase_number' => 'PO-EXPORT-001',
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('purchases.export'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
