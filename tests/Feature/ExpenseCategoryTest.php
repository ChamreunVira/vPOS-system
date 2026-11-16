<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the expense categories index with real data', function (): void {
    $category = ExpenseCategory::factory()->create([
        'name' => 'Store Utilities',
        'description' => 'Electricity, water and internet bills',
        'status' => 'active',
    ]);

    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => 120.50,
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(route('expense-categories.index'));

    $response->assertOk()
        ->assertViewIs('expense-categories.index')
        ->assertViewHas('categories');

    $categories = $response->viewData('categories');
    expect($categories->total())->toBe(1);
    expect($categories->first()->name)->toBe('Store Utilities');
    expect($categories->first()->expenses_count)->toBe(1);
    expect((float) $categories->first()->expenses_sum_amount)->toBe(120.50);
});

it('calculates total and active category counts and total spent', function (): void {
    $active = ExpenseCategory::factory()->create(['status' => 'active']);
    $inactive = ExpenseCategory::factory()->inactive()->create();

    Expense::factory()->forCategory($active)->forUser($this->user)->create(['amount' => 80.00]);
    Expense::factory()->forCategory($inactive)->forUser($this->user)->create(['amount' => 45.00]);

    $response = $this->get(route('expense-categories.index'));

    $response->assertOk();
    expect($response->viewData('totalCategories'))->toBe(2);
    expect($response->viewData('activeCategories'))->toBe(1);
    expect($response->viewData('totalSpent'))->toBe(125.0);
});

it('filters expense categories by search query', function (): void {
    ExpenseCategory::factory()->create(['name' => 'Cleaning Supplies', 'description' => 'Mops and soap']);
    ExpenseCategory::factory()->create(['name' => 'Store Rent', 'description' => 'Monthly lease payment']);

    $response = $this->get(route('expense-categories.index', ['q' => 'Cleaning']));

    $response->assertOk();
    $categories = $response->viewData('categories');
    expect($categories->total())->toBe(1);
    expect($categories->first()->name)->toBe('Cleaning Supplies');
});

it('filters expense categories by status', function (): void {
    $active = ExpenseCategory::factory()->create(['status' => 'active', 'name' => 'Active Cat']);
    $inactive = ExpenseCategory::factory()->inactive()->create(['name' => 'Inactive Cat']);

    $response = $this->get(route('expense-categories.index', ['status' => 'inactive']));

    $response->assertOk();
    $categories = $response->viewData('categories');
    expect($categories->total())->toBe(1);
    expect($categories->first()->id)->toBe($inactive->id);
});

it('creates a new expense category successfully', function (): void {
    $response = $this->post(route('expense-categories.store'), [
        'name' => 'Marketing & Ads',
        'description' => 'Social media ads and flyers',
        'status' => '1',
    ]);

    $response->assertRedirect(route('expense-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Marketing & Ads',
        'description' => 'Social media ads and flyers',
        'status' => 'active',
    ]);
});

it('validates that category name is required and unique', function (): void {
    ExpenseCategory::factory()->create(['name' => 'Office Supplies']);

    $response = $this->post(route('expense-categories.store'), [
        'name' => '',
    ]);
    $response->assertSessionHasErrors(['name']);

    $response = $this->post(route('expense-categories.store'), [
        'name' => 'Office Supplies',
    ]);
    $response->assertSessionHasErrors(['name']);
});

it('updates an existing expense category', function (): void {
    $category = ExpenseCategory::factory()->create([
        'name' => 'Old Category Name',
        'description' => 'Old description',
        'status' => 'active',
    ]);

    $response = $this->put(route('expense-categories.update', $category), [
        'name' => 'New Category Name',
        'description' => 'Updated notes',
        'status' => '0',
    ]);

    $response->assertRedirect(route('expense-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expense_categories', [
        'id' => $category->id,
        'name' => 'New Category Name',
        'description' => 'Updated notes',
        'status' => 'inactive',
    ]);
});

it('deletes an unused expense category', function (): void {
    $category = ExpenseCategory::factory()->create();

    $response = $this->delete(route('expense-categories.destroy', $category));

    $response->assertRedirect(route('expense-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('expense_categories', [
        'id' => $category->id,
    ]);
});

it('prevents deleting an expense category that has recorded expenses', function (): void {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->forCategory($category)->forUser($this->user)->create();

    $response = $this->delete(route('expense-categories.destroy', $category));

    $response->assertRedirect(route('expense-categories.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('expense_categories', [
        'id' => $category->id,
    ]);
});
