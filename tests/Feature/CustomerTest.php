<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('authenticated user can view customers index', function (): void {
    Customer::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('customers.index'));

    $response->assertOk()
        ->assertViewIs('customers.index')
        ->assertViewHas('customers');
});

test('authenticated user can create a customer with optional fields', function (): void {
    $response = $this->actingAs($this->user)->post(route('customers.store'), [
        'name' => 'John Doe',
        'phone' => '+855 12 345 678',
        'email' => 'john@example.com',
        'address' => 'Phnom Penh',
        'notes' => 'VIP regular',
        'status' => '1',
    ]);

    $response->assertRedirect(route('customers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'phone' => '+855 12 345 678',
        'email' => 'john@example.com',
        'status' => 'active',
    ]);
});

test('authenticated user can view customer profile with purchase history', function (): void {
    $customer = Customer::factory()->create(['name' => 'Jane Smith']);
    Order::factory()->create([
        'customer_id' => $customer->id,
        'total' => 50.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('customers.show', $customer));

    $response->assertOk()
        ->assertViewIs('customers.show')
        ->assertViewHas('customer')
        ->assertViewHas('totalSpent', 50.00);
});

test('authenticated user can update customer', function (): void {
    $customer = Customer::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($this->user)->put(route('customers.update', $customer), [
        'name' => 'New Name',
        'phone' => '098765432',
        'status' => '1',
    ]);

    $response->assertRedirect(route('customers.index'))
        ->assertSessionHas('success');

    expect($customer->fresh()->name)->toBe('New Name');
});

test('authenticated user can delete customer', function (): void {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});
