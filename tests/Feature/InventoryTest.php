<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('authenticated user can view the inventory index page', function (): void {
    $category = Category::factory()->create(['name' => 'Snacks']);
    $unit = Unit::factory()->create(['name' => 'Can', 'abbreviation' => 'can']);

    Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Crispy Chips',
        'stock' => 50,
        'min_stock' => 10,
    ]);

    $response = $this->actingAs($this->user)->get(route('inventory.index'));

    $response->assertOk()
        ->assertSee('Crispy Chips')
        ->assertSee('Snacks')
        ->assertSee('Stock overview')
        ->assertSee('Movement history');
});

test('inventory can be filtered by search and stock status', function (): void {
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'High Stock Item',
        'stock' => 100,
        'min_stock' => 10,
    ]);

    Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Low Stock Item',
        'stock' => 5,
        'min_stock' => 10,
    ]);

    Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Out of Stock Item',
        'stock' => 0,
        'min_stock' => 10,
    ]);

    $responseLow = $this->actingAs($this->user)->get(route('inventory.index', ['stock_status' => 'low_stock']));
    expect($responseLow->viewData('products')->pluck('name')->all())
        ->toContain('Low Stock Item')
        ->not->toContain('High Stock Item');

    $responseOut = $this->actingAs($this->user)->get(route('inventory.index', ['stock_status' => 'out_of_stock']));
    expect($responseOut->viewData('products')->pluck('name')->all())
        ->toContain('Out of Stock Item')
        ->not->toContain('High Stock Item');

    $responseSearch = $this->actingAs($this->user)->get(route('inventory.index', ['q' => 'High Stock']));
    expect($responseSearch->viewData('products')->pluck('name')->all())
        ->toContain('High Stock Item')
        ->not->toContain('Low Stock Item');
});

test('recording stock in increases product stock and creates stock movement', function (): void {
    $product = Product::factory()->create([
        'name' => 'Apple Juice',
        'stock' => 20,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => 15,
        'reason' => 'Supplier delivery',
        'note' => 'Delivery from Fresh Farms',
    ]);

    $response->assertRedirect(route('inventory.index', ['tab' => 'movements']));
    $response->assertSessionHas('success');

    $product->refresh();
    expect((float) $product->stock)->toBe(35.0);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'user_id' => $this->user->id,
        'type' => 'purchase',
        'quantity' => 15,
        'balance' => 35,
    ]);
});

test('recording stock out decreases product stock and creates stock movement', function (): void {
    $product = Product::factory()->create([
        'name' => 'Orange Juice',
        'stock' => 40,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'stock_out',
        'quantity' => 10,
        'reason' => 'Damaged',
        'note' => 'Spilled during shelving',
    ]);

    $response->assertRedirect(route('inventory.index', ['tab' => 'movements']));
    $response->assertSessionHas('success');

    $product->refresh();
    expect((float) $product->stock)->toBe(30.0);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'damage',
        'quantity' => -10,
        'balance' => 30,
    ]);
});

test('physical count adjustment updates stock to exact count', function (): void {
    $product = Product::factory()->create([
        'name' => 'Milk 1L',
        'stock' => 18,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'adjustment_mode' => 'count',
        'quantity' => 22,
        'reason' => 'Stock count correction',
        'note' => 'Found 4 additional units in storage',
    ]);

    $response->assertRedirect(route('inventory.index', ['tab' => 'movements']));

    $product->refresh();
    expect((float) $product->stock)->toBe(22.0);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 4,
        'balance' => 22,
    ]);
});

test('adjustment with delta mode adjusts relatively', function (): void {
    $product = Product::factory()->create([
        'name' => 'Cookies',
        'stock' => 15,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'adjustment_mode' => 'delta',
        'quantity' => 7,
        'reason' => 'Stock count correction',
        'note' => 'Added 7 boxes',
    ]);

    $response->assertRedirect(route('inventory.index', ['tab' => 'movements']));

    $product->refresh();
    expect((float) $product->stock)->toBe(22.0);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 7,
        'balance' => 22,
    ]);
});

test('adjustment with zero difference fails validation', function (): void {
    $product = Product::factory()->create([
        'name' => 'Bread',
        'stock' => 10,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'adjustment_mode' => 'count',
        'quantity' => 10,
        'reason' => 'Stock count correction',
    ]);

    $response->assertSessionHasErrors('quantity');

    $product->refresh();
    expect((float) $product->stock)->toBe(10.0);
});

test('cannot reduce stock below 0', function (): void {
    $product = Product::factory()->create([
        'name' => 'Limited Soda',
        'stock' => 5,
    ]);

    $response = $this->actingAs($this->user)->post(route('inventory.adjust'), [
        'product_id' => $product->id,
        'type' => 'stock_out',
        'quantity' => 10,
        'reason' => 'Damaged',
        'note' => 'Testing oversell',
    ]);

    $response->assertSessionHasErrors('quantity');

    $product->refresh();
    expect((float) $product->stock)->toBe(5.0);
});

test('movement history can be filtered by product and type', function (): void {
    $productA = Product::factory()->create(['name' => 'Product A']);
    $productB = Product::factory()->create(['name' => 'Product B']);

    StockMovement::factory()->create([
        'product_id' => $productA->id,
        'user_id' => $this->user->id,
        'type' => 'purchase',
        'quantity' => 10,
    ]);

    StockMovement::factory()->create([
        'product_id' => $productB->id,
        'user_id' => $this->user->id,
        'type' => 'damage',
        'quantity' => -2,
    ]);

    $responseProduct = $this->actingAs($this->user)->get(route('inventory.index', [
        'tab' => 'movements',
        'movement_product_id' => $productA->id,
    ]));

    expect($responseProduct->viewData('movements')->pluck('product_id')->all())
        ->toContain($productA->id)
        ->not->toContain($productB->id);

    $responseType = $this->actingAs($this->user)->get(route('inventory.index', [
        'tab' => 'movements',
        'movement_type' => 'damage',
    ]));

    expect($responseType->viewData('movements')->pluck('product_id')->all())
        ->toContain($productB->id)
        ->not->toContain($productA->id);
});

test('exporting stock report streams a valid csv file', function (): void {
    $category = Category::factory()->create(['name' => 'Beverages']);
    $unit = Unit::factory()->create(['name' => 'Bottle', 'abbreviation' => 'btl']);

    Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Mineral Water 500ml',
        'sku' => 'WAT-500',
        'cost' => '0.50',
        'price' => '1.00',
        'stock' => 50,
        'min_stock' => 10,
    ]);

    $response = $this->actingAs($this->user)->get(route('inventory.export'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Mineral Water 500ml')
        ->toContain('WAT-500')
        ->toContain('Beverages')
        ->toContain('In Stock')
        ->toContain('Stock Value');
});

test('product movements helper route redirects to filtered inventory movements tab', function (): void {
    $product = Product::factory()->create();

    $response = $this->actingAs($this->user)->get(route('products.movements', $product));

    $response->assertRedirect(route('inventory.index', [
        'tab' => 'movements',
        'movement_product_id' => $product->id,
    ]));
});
