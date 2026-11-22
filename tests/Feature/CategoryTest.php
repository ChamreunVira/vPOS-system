<?php

use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('authenticated user can list categories with product counts', function (): void {
    Category::factory()->count(2)->create();

    $response = $this->actingAs($this->user)->get(route('categories.index'));

    $response->assertOk()
        ->assertViewIs('categories.index')
        ->assertViewHas('categories');
});

test('authenticated user can create category', function (): void {
    $response = $this->actingAs($this->user)->post(route('categories.store'), [
        'name' => 'Beverages',
        'description' => 'Cold drinks and coffee',
        'status' => '1',
    ]);

    $response->assertRedirect(route('categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('categories', [
        'name' => 'Beverages',
        'status' => 'active',
    ]);
});

test('authenticated user can update category', function (): void {
    $category = Category::factory()->create(['name' => 'Old Category']);

    $response = $this->actingAs($this->user)->put(route('categories.update', $category), [
        'name' => 'New Category',
        'description' => 'Updated description',
        'status' => '1',
    ]);

    $response->assertRedirect(route('categories.index'))
        ->assertSessionHas('success');

    expect($category->fresh()->name)->toBe('New Category');
});

test('authenticated user can delete category', function (): void {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
