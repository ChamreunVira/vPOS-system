<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the expenses index with real data', function () {
    $category = ExpenseCategory::factory()->create(['name' => 'Rent']);
    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => 450.00,
        'expense_date' => now()->toDateString(),
        'description' => 'Monthly office rent',
    ]);

    $response = $this->get(route('expenses.index'));

    $response->assertOk()
        ->assertViewIs('expenses.index')
        ->assertViewHas('expenses');

    $expenses = $response->viewData('expenses');
    expect($expenses->total())->toBe(1);
    expect($expenses->first()->description)->toBe('Monthly office rent');
});

it('filters expenses by category', function () {
    $rent = ExpenseCategory::factory()->create(['name' => 'Rent']);
    $util = ExpenseCategory::factory()->create(['name' => 'Utilities']);

    Expense::factory()->forCategory($rent)->forUser($this->user)->create(['description' => 'Rent payment']);
    Expense::factory()->forCategory($util)->forUser($this->user)->create(['description' => 'Electric bill']);

    $response = $this->get(route('expenses.index', ['category_id' => $rent->id]));

    $response->assertOk();
    $expenses = $response->viewData('expenses');
    expect($expenses->total())->toBe(1);
    expect($expenses->first()->expense_category_id)->toBe($rent->id);
});

it('filters expenses by date range', function () {
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'expense_date' => '2026-01-15',
        'description' => 'Old expense',
    ]);
    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'expense_date' => now()->toDateString(),
        'description' => 'Recent expense',
    ]);

    $response = $this->get(route('expenses.index', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
    ]));

    $response->assertOk();
    $expenses = $response->viewData('expenses');
    expect($expenses->total())->toBe(1);
    expect($expenses->first()->description)->toBe('Recent expense');
});

it('filters expenses by search query on description', function () {
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->forCategory($category)->forUser($this->user)->create(['description' => 'Office rent payment']);
    Expense::factory()->forCategory($category)->forUser($this->user)->create(['description' => 'Electric bill']);

    $response = $this->get(route('expenses.index', ['q' => 'rent']));

    $response->assertOk();
    $expenses = $response->viewData('expenses');
    expect($expenses->total())->toBe(1);
});

it('records a new expense successfully', function () {
    $category = ExpenseCategory::factory()->create();

    $response = $this->post(route('expenses.store'), [
        'expense_category_id' => $category->id,
        'amount' => '75.50',
        'expense_date' => now()->toDateString(),
        'description' => 'Staff lunch',
    ]);

    $response->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'expense_category_id' => $category->id,
        'amount' => '75.50',
        'description' => 'Staff lunch',
        'created_by' => $this->user->id,
    ]);
});

it('validates expense amount must be positive', function () {
    $category = ExpenseCategory::factory()->create();

    $response = $this->post(route('expenses.store'), [
        'expense_category_id' => $category->id,
        'amount' => '-10',
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors(['amount']);
});

it('validates expense date cannot be in the future', function () {
    $category = ExpenseCategory::factory()->create();

    $response = $this->post(route('expenses.store'), [
        'expense_category_id' => $category->id,
        'amount' => '50',
        'expense_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertSessionHasErrors(['expense_date']);
});

it('validates expense category must exist', function () {
    $response = $this->post(route('expenses.store'), [
        'expense_category_id' => 9999,
        'amount' => '50',
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors(['expense_category_id']);
});

it('updates an expense', function () {
    $category = ExpenseCategory::factory()->create();
    $other = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => '100.00',
        'description' => 'Old description',
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->put(route('expenses.update', $expense), [
        'expense_category_id' => $other->id,
        'amount' => '200.00',
        'expense_date' => now()->toDateString(),
        'description' => 'Updated description',
    ]);

    $response->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'expense_category_id' => $other->id,
        'amount' => '200.00',
        'description' => 'Updated description',
    ]);
});

it('deletes an expense', function () {
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->forCategory($category)->forUser($this->user)->create();

    $response = $this->delete(route('expenses.destroy', $expense));

    $response->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

it('shows correct this-month total in stats', function () {
    $category = ExpenseCategory::factory()->create();

    // This month
    Expense::factory()->forCategory($category)->forUser($this->user)->thisMonth()->create(['amount' => 100]);
    Expense::factory()->forCategory($category)->forUser($this->user)->thisMonth()->create(['amount' => 50]);

    // Last month — should NOT count
    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => 999,
        'expense_date' => now()->subMonth()->toDateString(),
    ]);

    $response = $this->get(route('expenses.index'));

    $response->assertOk();
    expect((float) $response->viewData('thisMonthTotal'))->toBe(150.0);
});

it('exports expenses as CSV', function () {
    $category = ExpenseCategory::factory()->create(['name' => 'Utilities']);
    Expense::factory()->forCategory($category)->forUser($this->user)->create([
        'amount' => 99.99,
        'description' => 'Water bill',
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(route('expenses.export'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Water bill');
    expect($content)->toContain('99.99');
});
