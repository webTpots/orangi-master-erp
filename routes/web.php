<?php

use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\FlipkartController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DesignController;
use App\Http\Controllers\Admin\GstController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LabelController;
use App\Http\Controllers\Admin\ManifestController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\ExceptionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\SkuController;
use App\Http\Controllers\Admin\SkuMappingController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\MarketplaceConfigController;
use App\Http\Controllers\Admin\ReorderController;
use App\Http\Controllers\Admin\VendorPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Designs (full CRUD)
    Route::resource('designs', DesignController::class);

    // SKUs (read-only, managed via designs)
    Route::get('skus', [SkuController::class, 'index'])->name('skus.index');
    Route::get('skus/{sku}', [SkuController::class, 'show'])->name('skus.show');

    // SKU Mappings
    Route::resource('sku-mappings', SkuMappingController::class)->except(['edit', 'update']);
    Route::post('sku-mappings/{skuMapping}/approve', [SkuMappingController::class, 'approve'])->name('sku-mappings.approve');
    Route::post('sku-mappings/{skuMapping}/reject', [SkuMappingController::class, 'reject'])->name('sku-mappings.reject');

    // Product Import
    Route::get('products/import', [ProductImportController::class, 'showImport'])->name('products.import');
    Route::post('products/import', [ProductImportController::class, 'processImport'])->name('products.import.process');
    Route::post('products/import/confirm', [ProductImportController::class, 'confirmImport'])->name('products.import.confirm');

    // ── Vendor Management ────────────────────────────────────────
    Route::resource('vendors', VendorController::class);

    // ── Purchase Orders ──────────────────────────────────────────
    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveForm'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveStore'])->name('purchase-orders.receive.store');

    // ── Order Management ──────────────────────────────────────
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/status', [OrderController::class, 'changeStatus'])->name('orders.change-status');
    Route::post('orders/bulk-status', [OrderController::class, 'bulkChangeStatus'])->name('orders.bulk-status');

    // ── Label Import ──────────────────────────────────────────
    Route::prefix('labels')->name('labels.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::get('/upload', [LabelController::class, 'upload'])->name('upload');
        Route::post('/import', [LabelController::class, 'import'])->name('import');
        Route::get('/{label}', [LabelController::class, 'show'])->name('show');
    });

    // ── Manifest Import ───────────────────────────────────────
    Route::prefix('manifests')->name('manifests.')->group(function () {
        Route::get('/', [ManifestController::class, 'index'])->name('index');
        Route::get('/upload', [ManifestController::class, 'upload'])->name('upload');
        Route::post('/import', [ManifestController::class, 'import'])->name('import');
        Route::get('/{manifest}', [ManifestController::class, 'show'])->name('show');
        Route::get('/{manifest}/reconcile', [ManifestController::class, 'reconcile'])->name('reconcile');
    });

    // ── Shipment Management ──────────────────────────────────
    Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('shipments/from-order/{order}', [ShipmentController::class, 'createFromOrder'])->name('shipments.create-from-order');
    Route::post('shipments/{shipment}/dispatch', [ShipmentController::class, 'dispatch'])->name('shipments.dispatch');
    Route::post('shipments/bulk-dispatch', [ShipmentController::class, 'bulkDispatch'])->name('shipments.bulk-dispatch');

    // ── Scanning ──────────────────────────────────────────────
    Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('scan/process', [ScanController::class, 'processScan'])->name('scan.process');
    Route::get('scan/recent', [ScanController::class, 'recentScans'])->name('scan.recent');

    // ── Inventory Management ───────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/export', [InventoryController::class, 'export'])->name('export');
        Route::get('/adjust/create', [InventoryController::class, 'adjustCreate'])->name('adjust.create');
        Route::post('/adjust', [InventoryController::class, 'adjustStore'])->name('adjust.store');
        Route::get('/transfer/create', [InventoryController::class, 'transferCreate'])->name('transfer.create');
        Route::post('/transfer', [InventoryController::class, 'transferStore'])->name('transfer.store');
        Route::get('/opening-stock', [InventoryController::class, 'openingStock'])->name('opening-stock');
        Route::post('/opening-stock', [InventoryController::class, 'openingStockStore'])->name('opening-stock.store');
        Route::get('/physical-count', [InventoryController::class, 'physicalCount'])->name('physical-count');
        Route::post('/physical-count', [InventoryController::class, 'physicalCountStore'])->name('physical-count.store');
        Route::get('/{sku}', [InventoryController::class, 'show'])->name('show');
    });

    // ── Settlements ─────────────────────────────────────────────
    Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::get('settlements/upload', [SettlementController::class, 'upload'])->name('settlements.upload');
    Route::post('settlements/import', [SettlementController::class, 'import'])->name('settlements.import');
    Route::get('settlements/{settlement}', [SettlementController::class, 'show'])->name('settlements.show');
    Route::post('settlements/{settlement}/reconcile', [SettlementController::class, 'reconcile'])->name('settlements.reconcile');
    Route::post('settlements/{settlement}/close', [SettlementController::class, 'close'])->name('settlements.close');
    Route::post('settlement-lines/{line}/match', [SettlementController::class, 'matchLine'])->name('settlement-lines.match');
    Route::post('settlement-lines/{line}/dispute', [SettlementController::class, 'disputeLine'])->name('settlement-lines.dispute');

    // ── Payments ────────────────────────────────────────────────
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');

    // ── Returns ───────────────────────────────────────────────
    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('returns', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('returns/{returnOrder}', [ReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{returnOrder}/receive', [ReturnController::class, 'receive'])->name('returns.receive');
    Route::get('returns/{returnOrder}/inspect', [ReturnController::class, 'inspect'])->name('returns.inspect');
    Route::post('returns/{returnOrder}/inspect', [ReturnController::class, 'storeInspection'])->name('returns.store-inspection');
    Route::post('returns/{returnOrder}/restock', [ReturnController::class, 'restock'])->name('returns.restock');
    Route::post('returns/{returnOrder}/close', [ReturnController::class, 'close'])->name('returns.close');

    // ── Claims ────────────────────────────────────────────────
    Route::get('claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('claims/create', [ClaimController::class, 'create'])->name('claims.create');
    Route::post('claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::get('claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
    Route::post('claims/{claim}/file', [ClaimController::class, 'file'])->name('claims.file');
    Route::post('claims/{claim}/status', [ClaimController::class, 'updateStatus'])->name('claims.update-status');
    Route::post('claims/{claim}/communication', [ClaimController::class, 'addCommunication'])->name('claims.add-communication');
    Route::post('claims/{claim}/settle', [ClaimController::class, 'settle'])->name('claims.settle');

    // ── GST ─────────────────────────────────────────────────────
    Route::get('gst', [GstController::class, 'index'])->name('gst.index');
    Route::get('gst/summary', [GstController::class, 'summary'])->name('gst.summary');
    Route::get('gst/tcs-tds', [GstController::class, 'tcsTds'])->name('gst.tcs-tds');

    // ── Bank Accounts ─────────────────────────────────────────────
    Route::resource('bank-accounts', BankAccountController::class)->except(['destroy']);

    // ── Bank Reconciliation ───────────────────────────────────────
    Route::get('bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::get('bank-reconciliation/transactions', [BankReconciliationController::class, 'transactions'])->name('bank-reconciliation.transactions');
    Route::get('bank-reconciliation/import', [BankReconciliationController::class, 'import'])->name('bank-reconciliation.import');
    Route::post('bank-reconciliation/import', [BankReconciliationController::class, 'processImport'])->name('bank-reconciliation.process-import');
    Route::post('bank-reconciliation/auto-reconcile', [BankReconciliationController::class, 'autoReconcile'])->name('bank-reconciliation.auto-reconcile');
    Route::match(['get', 'post'], 'bank-transactions/{transaction}/match', [BankReconciliationController::class, 'matchTransaction'])->name('bank-reconciliation.match');
    Route::post('bank-transactions/{transaction}/unmatch', [BankReconciliationController::class, 'unmatchTransaction'])->name('bank-reconciliation.unmatch');
    Route::post('bank-transactions/{transaction}/ignore', [BankReconciliationController::class, 'ignoreTransaction'])->name('bank-reconciliation.ignore');
    Route::get('bank-reconciliation/rules', [BankReconciliationController::class, 'rules'])->name('bank-reconciliation.rules');
    Route::post('bank-reconciliation/rules', [BankReconciliationController::class, 'storeRule'])->name('bank-reconciliation.store-rule');

    // ── AI Center ────────────────────────────────────────────────
    Route::get('ai', [AiController::class, 'dashboard'])->name('ai.dashboard');
    Route::get('ai/suggestions', [AiController::class, 'suggestions'])->name('ai.suggestions');
    Route::post('ai/suggestions/{suggestion}/accept', [AiController::class, 'acceptSuggestion'])->name('ai.accept-suggestion');
    Route::post('ai/suggestions/{suggestion}/reject', [AiController::class, 'rejectSuggestion'])->name('ai.reject-suggestion');
    Route::get('ai/anomalies', [AiController::class, 'anomalies'])->name('ai.anomalies');
    Route::post('ai/anomalies/detect', [AiController::class, 'runDetection'])->name('ai.run-detection');
    Route::get('ai/forecast', [AiController::class, 'forecast'])->name('ai.forecast');
    Route::get('ai/document-analyzer', [AiController::class, 'documentAnalyzer'])->name('ai.document-analyzer');
    Route::post('ai/document-analyzer', [AiController::class, 'analyzeDocument'])->name('ai.analyze-document');
    Route::get('ai/sku-matcher', [AiController::class, 'skuMatcher'])->name('ai.sku-matcher');
    Route::post('ai/sku-matcher', [AiController::class, 'matchSku'])->name('ai.match-sku');

    // ── Flipkart ──────────────────────────────────────────────
    Route::get('flipkart', [FlipkartController::class, 'dashboard'])->name('flipkart.dashboard');
    Route::get('flipkart/sku-mapping', [FlipkartController::class, 'skuMapping'])->name('flipkart.sku-mapping');
    Route::post('flipkart/sku-mapping', [FlipkartController::class, 'storeMapping'])->name('flipkart.store-mapping');
    Route::get('flipkart/import-labels', [FlipkartController::class, 'importLabels'])->name('flipkart.import-labels');
    Route::post('flipkart/import-labels', [FlipkartController::class, 'processLabels'])->name('flipkart.process-labels');
    Route::get('flipkart/import-settlement', [FlipkartController::class, 'importSettlement'])->name('flipkart.import-settlement');
    Route::post('flipkart/import-settlement', [FlipkartController::class, 'processSettlement'])->name('flipkart.process-settlement');

    // ── Exception Center ──────────────────────────────────────────
    Route::get('exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
    Route::get('exceptions/categories', [ExceptionController::class, 'categories'])->name('exceptions.categories');
    Route::post('exceptions/categories', [ExceptionController::class, 'storeCategory'])->name('exceptions.store-category');
    Route::post('exceptions/auto-detect', [ExceptionController::class, 'autoDetect'])->name('exceptions.auto-detect');
    Route::get('exceptions/{exception}', [ExceptionController::class, 'show'])->name('exceptions.show');
    Route::post('exceptions/{exception}/assign', [ExceptionController::class, 'assign'])->name('exceptions.assign');
    Route::post('exceptions/{exception}/escalate', [ExceptionController::class, 'escalate'])->name('exceptions.escalate');
    Route::post('exceptions/{exception}/resolve', [ExceptionController::class, 'resolve'])->name('exceptions.resolve');
    Route::post('exceptions/{exception}/comment', [ExceptionController::class, 'addComment'])->name('exceptions.add-comment');

    // ── Notifications ─────────────────────────────────────────────
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.update-preferences');

    // ── Analytics ───────────────────────────────────────────────
    Route::get('analytics', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('analytics/profit-loss', [AnalyticsController::class, 'profitAndLoss'])->name('analytics.profit-loss');
    Route::get('analytics/designs', [AnalyticsController::class, 'designPerformance'])->name('analytics.designs');
    Route::get('analytics/marketplaces', [AnalyticsController::class, 'marketplacePerformance'])->name('analytics.marketplaces');
    Route::get('analytics/vendors', [AnalyticsController::class, 'vendorPerformance'])->name('analytics.vendors');
    Route::get('analytics/inventory', [AnalyticsController::class, 'inventoryAnalytics'])->name('analytics.inventory');

    // ── Vendor Portal Management ─────────────────────────────────
    Route::get('vendor-portal/tokens', [VendorPortalController::class, 'tokens'])->name('vendor-portal.tokens');
    Route::post('vendor-portal/tokens', [VendorPortalController::class, 'generateToken'])->name('vendor-portal.generate-token');
    Route::post('vendor-portal/tokens/{token}/revoke', [VendorPortalController::class, 'revokeToken'])->name('vendor-portal.revoke-token');

    // ── Reorder Engine ───────────────────────────────────────────
    Route::get('reorder', [ReorderController::class, 'dashboard'])->name('reorder.dashboard');
    Route::get('reorder/suggestions', [ReorderController::class, 'suggestions'])->name('reorder.suggestions');
    Route::post('reorder/run-check', [ReorderController::class, 'runCheck'])->name('reorder.run-check');
    Route::post('reorder/suggestions/{suggestion}/convert', [ReorderController::class, 'convertToPo'])->name('reorder.convert-to-po');
    Route::post('reorder/suggestions/bulk-convert', [ReorderController::class, 'bulkConvertToPo'])->name('reorder.bulk-convert');
    Route::get('reorder/rules', [ReorderController::class, 'rules'])->name('reorder.rules');
    Route::post('reorder/rules', [ReorderController::class, 'storeRule'])->name('reorder.store-rule');
    Route::post('reorder/rules/{rule}/toggle', [ReorderController::class, 'toggleRule'])->name('reorder.toggle-rule');

    // ── Marketplace Config ───────────────────────────────────────
    Route::get('marketplace-config', [MarketplaceConfigController::class, 'index'])->name('marketplace-config.index');
    Route::get('marketplace-config/comparison', [MarketplaceConfigController::class, 'comparison'])->name('marketplace-config.comparison');
    Route::get('marketplace-config/{marketplace}/edit', [MarketplaceConfigController::class, 'edit'])->name('marketplace-config.edit');
    Route::put('marketplace-config/{marketplace}', [MarketplaceConfigController::class, 'update'])->name('marketplace-config.update');
});

/*
|--------------------------------------------------------------------------
| Vendor Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('vendor-portal')->name('vendor.')->group(function () {
    Route::get('login', [VendorDashboardController::class, 'login'])->name('login');
    Route::post('login', [VendorDashboardController::class, 'authenticate'])->name('authenticate');
    Route::post('logout', [VendorDashboardController::class, 'logout'])->name('logout');

    Route::middleware('vendor.portal')->group(function () {
        Route::get('dashboard', [VendorDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('purchase-orders', [VendorDashboardController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('purchase-orders/{po}', [VendorDashboardController::class, 'showPurchaseOrder'])->name('purchase-orders.show');
        Route::post('purchase-orders/{po}/confirm', [VendorDashboardController::class, 'confirmOrder'])->name('purchase-orders.confirm');
        Route::post('purchase-orders/{po}/delivery', [VendorDashboardController::class, 'updateDelivery'])->name('purchase-orders.delivery');
    });
});

// Legacy dashboard route — redirects to admin dashboard for backward compatibility
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
