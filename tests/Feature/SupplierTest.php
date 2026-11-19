<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user can view suppliers index', function (): void {
    Supplier::factory()->create(['name' => 'Acme Supplies']);

    $response = $this->actingAs($this->user)->get(route('suppliers.index'));

    $response->assertOk()
        ->assertSee('Suppliers')
        ->assertSee('Acme Supplies');
});

test('suppliers can be searched by name, phone, or email', function (): void {
    Supplier::factory()->create([
        'name' => 'Phnom Penh Beverage Co',
        'phone' => '012345678',
        'email' => 'contact@ppbev.com',
    ]);
    Supplier::factory()->create([
        'name' => 'Siem Reap Bakery',
        'phone' => '098765432',
        'email' => 'sales@srbakery.com',
    ]);

    $response = $this->actingAs($this->user)->get(route('suppliers.index', ['search' => 'Phnom Penh']));
    $response->assertOk()
        ->assertSee('Phnom Penh Beverage Co')
        ->assertDontSee('Siem Reap Bakery');

    $responseByPhone = $this->actingAs($this->user)->get(route('suppliers.index', ['search' => '098765432']));
    $responseByPhone->assertOk()
        ->assertSee('Siem Reap Bakery')
        ->assertDontSee('Phnom Penh Beverage Co');
});

test('user can view create supplier page', function (): void {
    $response = $this->actingAs($this->user)->get(route('suppliers.create'));

    $response->assertOk()
        ->assertSee('New supplier')
        ->assertSee('Supplier name');
});

test('user can store a new supplier', function (): void {
    $payload = [
        'name' => 'Global Snacks Ltd',
        'phone' => '023888999',
        'email' => 'orders@globalsnacks.com',
        'address' => 'Building 4B, Russian Blvd, Phnom Penh',
        'status' => '1',
    ];

    $response = $this->actingAs($this->user)->post(route('suppliers.store'), $payload);

    $response->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Global Snacks Ltd',
        'phone' => '023888999',
        'email' => 'orders@globalsnacks.com',
        'status' => 'active',
    ]);
});

test('storing supplier validates required fields', function (): void {
    $response = $this->actingAs($this->user)->post(route('suppliers.store'), []);

    $response->assertSessionHasErrors(['name', 'phone', 'email', 'address']);
});

test('user can view edit supplier page', function (): void {
    $supplier = Supplier::factory()->create(['name' => 'Sunrise Dairy']);

    $response = $this->actingAs($this->user)->get(route('suppliers.edit', $supplier));

    $response->assertOk()
        ->assertSee('Edit supplier')
        ->assertSee('Sunrise Dairy');
});

test('user can update an existing supplier', function (): void {
    $supplier = Supplier::factory()->create([
        'name' => 'Old Name',
        'phone' => '012111222',
        'email' => 'old@supplier.com',
    ]);

    $response = $this->actingAs($this->user)->put(route('suppliers.update', $supplier), [
        'name' => 'Updated Supplier Name',
        'phone' => '012999000',
        'email' => 'updated@supplier.com',
        'address' => 'New Address 123',
        'status' => '1',
    ]);

    $response->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'Updated Supplier Name',
        'phone' => '012999000',
        'email' => 'updated@supplier.com',
        'status' => 'active',
    ]);
});

test('user can delete a supplier without purchase orders', function (): void {
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('suppliers', [
        'id' => $supplier->id,
    ]);
});

test('deleting a supplier with purchase orders deactivates instead of deleting', function (): void {
    $supplier = Supplier::factory()->create(['status' => 'active']);
    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'status' => 'inactive',
    ]);
});
