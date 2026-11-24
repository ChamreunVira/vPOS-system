<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Pos\MobileScannerController;
use App\Http\Controllers\Pos\ScannerSessionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOcrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/pos');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('pos/scanner/session', [ScannerSessionController::class, 'store'])->name('pos.scanner.session');
    Route::get('pos/scanner/session/status', [ScannerSessionController::class, 'status'])->name('pos.scanner.status');
    Route::post('pos/scanner/session/regenerate', [ScannerSessionController::class, 'regenerate'])->name('pos.scanner.regenerate');
    Route::post('pos/scanner/session/disconnect', [ScannerSessionController::class, 'disconnect'])->name('pos.scanner.disconnect');
    Route::get('pos/scanner/events', [ScannerSessionController::class, 'events'])->name('pos.scanner.events');

    Route::post('products/recognize-image', [ProductOcrController::class, 'recognize'])->name('products.recognize-image');
    Route::resource('products', ProductController::class);
    Route::get('products/{product}/movements', [InventoryController::class, 'productMovements'])->name('products.movements');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('units', UnitController::class)->only(['index', 'store', 'update']);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('inventory/export', [InventoryController::class, 'export'])->name('inventory.export');

    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');

    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::get('purchases/export', [PurchaseOrderController::class, 'export'])->name('purchases.export');
    Route::post('purchases/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchases.cancel');
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/cash-movement', [PaymentController::class, 'cashMovement'])->name('payments.cash-movement');

    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('language', [LocaleController::class, 'update'])->name('locale.update');
    Route::put('roles/permissions', [RolePermissionController::class, 'updateMatrix'])->name('roles.permissions.update');
    Route::resource('employees', EmployeeController::class)->except(['show']);

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

Route::prefix('pos/scanner')->group(function () {
    Route::get('/', [MobileScannerController::class, 'index'])->name('pos.scanner.mobile');
    Route::post('pair/qr', [MobileScannerController::class, 'pairQr'])->name('pos.scanner.pair.qr');
    Route::post('pair/code', [MobileScannerController::class, 'pairCode'])->middleware('throttle:5,1')->name('pos.scanner.pair.code');

    Route::middleware('scanner.device')->group(function () {
        Route::get('status', [MobileScannerController::class, 'status'])->name('pos.scanner.device.status');
        Route::post('scan', [MobileScannerController::class, 'scan'])->middleware('throttle:120,1')->name('pos.scanner.device.scan');
        Route::post('disconnect', [MobileScannerController::class, 'disconnect'])->name('pos.scanner.device.disconnect');
    });
});

require __DIR__.'/auth.php';
