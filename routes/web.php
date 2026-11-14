<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('units', UnitController::class)->only(['index', 'store', 'update']);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/cash-movement', [PaymentController::class, 'cashMovement'])->name('payments.cash-movement');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('language', [LocaleController::class, 'update'])->name('locale.update');
    Route::put('roles/permissions', [RolePermissionController::class, 'updateMatrix'])->name('roles.permissions.update');
    Route::resource('employees', EmployeeController::class)->except(['show']);

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
})->middleware(['auth', 'verified']);

require __DIR__.'/auth.php';
