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

it('redirects expense-categories.index to expenses.index with categories tab', function (): void {
    $response = $this->get(route('expense-categories.index'));

    $response->assertRedirect(route('expenses.index', ['tab' => 'categories']));
});

it('renders the expense categories tab with real data', function (): void {
    $category = ExpenseCategory::factory()->create([
        'name' => 'Store Utilities',
        'description' => 'Electricity, water and internet bills',
        'status' => 'active',
    ]);

    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => 120.50,
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(route('expenses.index', ['tab' => 'categories']));

    $response->assertOk()
        ->assertViewIs('expenses.index')
        ->assertViewHas('categoryItems');

    $categoryItems = $response->viewData('categoryItems');
    expect($categoryItems->count())->toBe(1);
    expect($categoryItems->first()->name)->toBe('Store Utilities');
    expect($categoryItems->first()->expenses_count)->toBe(1);
    expect((float) $categoryItems->first()->expenses_sum_amount)->toBe(120.50);
});

it('calculates total and active category counts', function (): void {
    $active = ExpenseCategory::factory()->create(['status' => 'active']);
    $inactive = ExpenseCategory::factory()->inactive()->create();

    $response = $this->get(route('expenses.index', ['tab' => 'categories']));

    $response->assertOk();
    expect($response->viewData('totalCategories'))->toBe(2);
    expect($response->viewData('activeCategoriesCount'))->toBe(1);
});

it('filters expense categories by search query on categories tab', function (): void {
    ExpenseCategory::factory()->create(['name' => 'Cleaning Supplies', 'description' => 'Mops and soap']);
    ExpenseCategory::factory()->create(['name' => 'Store Rent', 'description' => 'Monthly lease payment']);

    $response = $this->get(route('expenses.index', ['tab' => 'categories', 'cat_q' => 'Cleaning']));

    $response->assertOk();
    $categoryItems = $response->viewData('categoryItems');
    expect($categoryItems->count())->toBe(1);
    expect($categoryItems->first()->name)->toBe('Cleaning Supplies');
});

it('filters expense categories by status on categories tab', function (): void {
    $active = ExpenseCategory::factory()->create(['status' => 'active', 'name' => 'Active Cat']);
    $inactive = ExpenseCategory::factory()->inactive()->create(['name' => 'Inactive Cat']);

    $response = $this->get(route('expenses.index', ['tab' => 'categories', 'cat_status' => 'inactive']));

    $response->assertOk();
    $categoryItems = $response->viewData('categoryItems');
    expect($categoryItems->count())->toBe(1);
    expect($categoryItems->first()->id)->toBe($inactive->id);
});

it('creates a new expense category successfully and redirects to categories tab', function (): void {
    $response = $this->post(route('expense-categories.store'), [
        'name' => 'Marketing & Ads',
        'description' => 'Social media ads and flyers',
        'status' => '1',
    ]);

    $response->assertRedirect(route('expenses.index', ['tab' => 'categories']))
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

it('updates an existing expense category and redirects to categories tab', function (): void {
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

    $response->assertRedirect(route('expenses.index', ['tab' => 'categories']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expense_categories', [
        'id' => $category->id,
        'name' => 'New Category Name',
        'description' => 'Updated notes',
        'status' => 'inactive',
    ]);
});

it('deletes an unused expense category and redirects to categories tab', function (): void {
    $category = ExpenseCategory::factory()->create();

    $response = $this->delete(route('expense-categories.destroy', $category));

    $response->assertRedirect(route('expenses.index', ['tab' => 'categories']))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('expense_categories', [
        'id' => $category->id,
    ]);
});

it('prevents deleting an expense category that has recorded expenses', function (): void {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->forCategory($category)->forUser($this->user)->create();

    $response = $this->delete(route('expense-categories.destroy', $category));

    $response->assertRedirect(route('expenses.index', ['tab' => 'categories']))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('expense_categories', [
        'id' => $category->id,
    ]);
});
