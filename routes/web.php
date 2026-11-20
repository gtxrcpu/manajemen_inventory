<?php

use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LocationContextController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockDisposalController;
use App\Http\Controllers\StockMovementHistoryController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\Admin\WhatsAppAlertController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PicSubstituteAssignmentController;
use App\Http\Controllers\Admin\SystemSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Dashboard Utama: Mobile-first operational dashboard (1:1 with dashboard.html)
| Inventory Dashboard: Desktop table view with FEFO analytics
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'permission:app.access', 'private.nocache', 'location.context'])->group(function () {
    Route::get('/account', function () {
        return view('auth.account');
    })->name('account');

    Route::get('/', DashboardController::class);

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', [GlobalSearchController::class, 'index'])->name('search.index');
    Route::post('/location-context', [LocationContextController::class, 'update'])->name('location-context.update');

    Route::get('/inventory', fn () => redirect()->route('inventory.stock.index'))->name('inventory.legacy');

    Route::get('/data-stock', [InventoryController::class, 'index'])->name('inventory.stock.index');
    Route::get('/data-stock/live', [InventoryController::class, 'live'])->name('inventory.stock.live');
    Route::get('/data-stock/{product}', [InventoryController::class, 'show'])->name('inventory.stock.show');
    Route::get('/stock-movements', [StockMovementHistoryController::class, 'index'])->name('inventory.movements.index');
    Route::get('/stock-movements/{stockMovement}', [StockMovementHistoryController::class, 'show'])->whereNumber('stockMovement')->name('inventory.movements.show');
    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('inventory.transactions.index');
    Route::get('/transaction-history/{type}/{id}', [TransactionHistoryController::class, 'show'])->whereNumber('id')->name('inventory.transactions.show');
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });
    Route::prefix('low-stock')->name('low-stock.')->group(function () {
        Route::get('/', [LowStockController::class, 'index'])->middleware('permission:low_stock.view')->name('index');
        Route::get('/thresholds', [LowStockController::class, 'thresholds'])->middleware('permission:low_stock.manage')->name('thresholds');
        Route::put('/thresholds', [LowStockController::class, 'saveThresholds'])->middleware('permission:low_stock.manage')->name('thresholds.save');
    });
    Route::middleware('permission:whatsapp.manage')->prefix('admin/whatsapp-alerts')->name('admin.whatsapp-alerts.')->group(function () {
        Route::get('/', [WhatsAppAlertController::class, 'index'])->name('index');
        Route::get('/status', [WhatsAppAlertController::class, 'status'])->name('status');
        Route::put('/', [WhatsAppAlertController::class, 'update'])->name('update');
    });
    Route::prefix('transfers')->name('inventory.transfers.')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->name('index');
        Route::get('/create', [StockTransferController::class, 'create'])->name('create');
        Route::post('/', [StockTransferController::class, 'store'])->name('store');
        Route::post('/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->whereNumber('stockTransfer')->name('approve');
        Route::post('/{stockTransfer}/eta', [StockTransferController::class, 'updateEta'])->whereNumber('stockTransfer')->name('eta');
        Route::get('/requests/create', [StockTransferController::class, 'createRequest'])->name('requests.create');
        Route::post('/requests', [StockTransferController::class, 'storeRequest'])->name('requests.store');
        Route::patch('/requests/{stockTransferRequest}/pick', [StockTransferController::class, 'storePick'])->whereNumber('stockTransferRequest')->name('requests.pick');
        Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])->whereNumber('stockTransfer')->name('show');
        Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->whereNumber('stockTransfer')->name('receive');
        Route::post('/{stockTransfer}/discrepancies/{transferItem}/resolve', [StockTransferController::class, 'resolveDiscrepancy'])
            ->middleware('permission:transfer.resolve_discrepancy')->whereNumber('stockTransfer')->whereNumber('transferItem')->name('discrepancies.resolve');
    });
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/purchase-requests/{purchaseRequest}/approve', [ApprovalController::class, 'approvePurchaseRequest'])
        ->middleware('permission:purchase-request.approve')->whereNumber('purchaseRequest')->name('approvals.purchase-requests.approve');
    Route::post('/approvals/purchase-requests/{purchaseRequest}/reject', [ApprovalController::class, 'rejectPurchaseRequest'])
        ->middleware('permission:purchase-request.approve')->whereNumber('purchaseRequest')->name('approvals.purchase-requests.reject');
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/', [PurchasingController::class, 'index'])->name('index');
        Route::get('/requests/create', [PurchasingController::class, 'createRequest'])->name('requests.create');
        Route::post('/requests', [PurchasingController::class, 'storeRequest'])->name('requests.store');
        Route::get('/requests/estimate', [PurchasingController::class, 'estimateRequestItem'])->name('requests.estimate');
        Route::get('/requests/{purchaseRequest}/edit', [PurchasingController::class, 'editRequest'])->whereNumber('purchaseRequest')->name('requests.edit');
        Route::put('/requests/{purchaseRequest}', [PurchasingController::class, 'updateRequest'])->whereNumber('purchaseRequest')->name('requests.update');
        Route::get('/requests/{purchaseRequest}', [PurchasingController::class, 'showRequest'])->whereNumber('purchaseRequest')->name('requests.show');
        Route::get('/requests/{purchaseRequest}/order/create', [PurchasingController::class, 'createOrder'])->whereNumber('purchaseRequest')->name('orders.create');
        Route::post('/requests/{purchaseRequest}/order', [PurchasingController::class, 'storeOrder'])->whereNumber('purchaseRequest')->name('orders.store');
        Route::get('/orders/create', [PurchasingController::class, 'createDirectOrder'])->name('orders.direct.create');
        Route::post('/orders', [PurchasingController::class, 'storeDirectOrder'])->name('orders.direct.store');
        Route::get('/orders/{purchaseOrder}/edit', [PurchasingController::class, 'editDraftOrder'])->whereNumber('purchaseOrder')->name('orders.edit');
        Route::put('/orders/{purchaseOrder}', [PurchasingController::class, 'updateDraftOrder'])->whereNumber('purchaseOrder')->name('orders.update');
        Route::post('/orders/{purchaseOrder}/issue', [PurchasingController::class, 'issueOrder'])->middleware('permission:purchasing.manage')->whereNumber('purchaseOrder')->name('orders.issue');
        Route::post('/orders/{purchaseOrder}/cancel', [PurchasingController::class, 'cancelOrder'])->middleware('permission:purchasing.manage')->whereNumber('purchaseOrder')->name('orders.cancel');
        Route::get('/orders/{purchaseOrder}/print', [PurchasingController::class, 'printOrder'])->whereNumber('purchaseOrder')->name('orders.print');
        Route::get('/orders/{purchaseOrder}', [PurchasingController::class, 'showOrder'])->whereNumber('purchaseOrder')->name('orders.show');
        Route::post('/orders/{purchaseOrder}/supplier-follow-up', [PurchasingController::class, 'recordSupplierFollowUp'])->middleware('permission:purchasing.manage')->whereNumber('purchaseOrder')->name('orders.supplier-follow-up');
        Route::post('/orders/{purchaseOrder}/receive', [PurchasingController::class, 'receiveOrder'])->whereNumber('purchaseOrder')->name('orders.receive');
    });
    Route::prefix('purchase-invoices')->name('purchase-invoices.')->group(function () {
        Route::get('/', [PurchaseInvoiceController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseInvoiceController::class, 'create'])->name('create');
        Route::post('/', [PurchaseInvoiceController::class, 'store'])->name('store');
        Route::get('/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->whereNumber('purchaseInvoice')->name('show');
        Route::get('/{purchaseInvoice}/edit', [PurchaseInvoiceController::class, 'edit'])->whereNumber('purchaseInvoice')->name('edit');
        Route::put('/{purchaseInvoice}', [PurchaseInvoiceController::class, 'update'])->whereNumber('purchaseInvoice')->name('update');
        Route::post('/{purchaseInvoice}/submit', [PurchaseInvoiceController::class, 'submit'])->whereNumber('purchaseInvoice')->name('submit');
        Route::post('/{purchaseInvoice}/review', [PurchaseInvoiceController::class, 'review'])->whereNumber('purchaseInvoice')->name('review');
        Route::post('/{purchaseInvoice}/payments', [PurchaseInvoiceController::class, 'recordPayment'])->whereNumber('purchaseInvoice')->name('payments.store');
        Route::post('/{purchaseInvoice}/payments/{payment}/reverse', [PurchaseInvoiceController::class, 'reversePayment'])->whereNumber('purchaseInvoice')->whereNumber('payment')->name('payments.reverse');
        Route::post('/{purchaseInvoice}/link-receipts', [PurchaseInvoiceController::class, 'linkReceipts'])->whereNumber('purchaseInvoice')->name('receipts.link');
        Route::get('/{purchaseInvoice}/document', [PurchaseInvoiceController::class, 'document'])->whereNumber('purchaseInvoice')->name('document');
    });
    Route::get('/finance/valuation', [PurchaseInvoiceController::class, 'valuation'])->name('finance.valuation');
    Route::get('/finance/valuation/export', [PurchaseInvoiceController::class, 'exportValuation'])->name('finance.valuation.export');
    Route::get('/dashboard/stock-live', [DashboardController::class, 'stockLive'])->name('dashboard.stock-live');
    Route::get('/stock-in', [StockInController::class, 'index'])->name('inventory.stock-in.index');
    Route::get('/stock-in/purchase-orders', [PurchasingController::class, 'receivingOrders'])->name('inventory.stock-in.purchase-orders');
    Route::get('/stock-in/{stockIn}/evidence/{evidence}', [StockInController::class, 'evidence'])->whereNumber('stockIn')->whereNumber('evidence')->name('inventory.stock-in.evidence');
    Route::get('/stock-in/{stockIn}', [StockInController::class, 'show'])->whereNumber('stockIn')->name('inventory.stock-in.show');
    Route::middleware('permission:stock-in.approve')->group(function () {
        Route::post('/stock-in/{stockIn}/approve', [StockInController::class, 'approve'])->whereNumber('stockIn')->name('inventory.stock-in.approve');
        Route::post('/stock-in/{stockIn}/reject', [StockInController::class, 'reject'])->whereNumber('stockIn')->name('inventory.stock-in.reject');
    });
    Route::middleware('permission:stock.manage')->group(function () {
        Route::get('/stock-in/create', [StockInController::class, 'create'])->name('inventory.stock-in.create');
        Route::post('/stock-in', [StockInController::class, 'store'])->name('inventory.stock-in.store');
        Route::get('/stock-out', [StockOutController::class, 'index'])->name('inventory.stock-out.index');
        Route::get('/stock-out/create', [StockOutController::class, 'create'])->name('inventory.stock-out.create');
        Route::post('/stock-out', [StockOutController::class, 'store'])->name('inventory.stock-out.store');
        Route::get('/stock-out/{stockOut}', [StockOutController::class, 'show'])->whereNumber('stockOut')->name('inventory.stock-out.show');
    });
    Route::get('/stock-opname', [StockOpnameController::class, 'index'])->middleware('permission:opname.view')->name('inventory.stock-opname.index');
    Route::get('/stock-opname/create', [StockOpnameController::class, 'create'])->middleware('permission:opname.manage')->name('inventory.stock-opname.create');
    Route::post('/stock-opname', [StockOpnameController::class, 'start'])->middleware('permission:opname.manage')->name('inventory.stock-opname.start');
    Route::get('/stock-opname/{stockOpname}', [StockOpnameController::class, 'show'])->middleware('permission:opname.view')->whereNumber('stockOpname')->name('inventory.stock-opname.show');
    Route::post('/stock-opname/{stockOpname}/submit', [StockOpnameController::class, 'submit'])->middleware('permission:opname.manage')->whereNumber('stockOpname')->name('inventory.stock-opname.submit');
    Route::post('/stock-opname/{stockOpname}/cancel', [StockOpnameController::class, 'cancel'])->middleware('permission:opname.manage')->whereNumber('stockOpname')->name('inventory.stock-opname.cancel');
    Route::post('/stock-opname/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->middleware('permission:opname.approve')->whereNumber('stockOpname')->name('inventory.stock-opname.approve');
    Route::post('/stock-opname/{stockOpname}/reject', [StockOpnameController::class, 'reject'])->middleware('permission:opname.approve')->whereNumber('stockOpname')->name('inventory.stock-opname.reject');
    Route::prefix('stock-adjustments')->name('inventory.stock-adjustments.')->middleware('permission:adjust.manage')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index'])->name('index');
        Route::get('/create', [StockAdjustmentController::class, 'create'])->name('create');
        Route::post('/', [StockAdjustmentController::class, 'store'])->name('store');
        Route::get('/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->whereNumber('stockAdjustment')->name('show');
    });
    Route::prefix('stock-disposals')->name('inventory.stock-disposals.')->group(function () {
        Route::get('/', [StockDisposalController::class, 'index'])->name('index');
        Route::get('/create', [StockDisposalController::class, 'create'])->name('create');
        Route::post('/', [StockDisposalController::class, 'store'])->name('store');
        Route::get('/{stockDisposal}', [StockDisposalController::class, 'show'])->whereNumber('stockDisposal')->name('show');
        Route::post('/{stockDisposal}/approve', [StockDisposalController::class, 'approve'])->whereNumber('stockDisposal')->name('approve');
        Route::post('/{stockDisposal}/reject', [StockDisposalController::class, 'reject'])->whereNumber('stockDisposal')->name('reject');
        Route::post('/{stockDisposal}/supplier-resolution', [StockDisposalController::class, 'resolveSupplier'])->middleware('permission:disposal.resolve')->whereNumber('stockDisposal')->name('supplier-resolution');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:users.manage'])->group(function () {
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::post('/products/import', [MasterDataController::class, 'importProducts'])->name('products.import');
        Route::post('/categories/import', [MasterDataController::class, 'importCategories'])->name('categories.import');
        Route::get('/{resource}', [MasterDataController::class, 'index'])->name('index');
        Route::get('/{resource}/create', [MasterDataController::class, 'create'])->name('create');
        Route::post('/{resource}', [MasterDataController::class, 'store'])->name('store');
        Route::get('/{resource}/{id}', [MasterDataController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{resource}/{id}/edit', [MasterDataController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{resource}/{id}', [MasterDataController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{resource}/{id}', [MasterDataController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

    Route::get('/pic-substitutes', [PicSubstituteAssignmentController::class, 'index'])->name('pic-substitutes.index');
    Route::get('/pic-substitutes/create', [PicSubstituteAssignmentController::class, 'create'])->name('pic-substitutes.create');
    Route::post('/pic-substitutes', [PicSubstituteAssignmentController::class, 'store'])->name('pic-substitutes.store');
    Route::post('/pic-substitutes/{assignment}/revoke', [PicSubstituteAssignmentController::class, 'revoke'])->whereNumber('assignment')->name('pic-substitutes.revoke');

    Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::patch('/roles/{role}/permissions', [RolePermissionController::class, 'update'])
        ->name('roles.permissions.update');
});
