<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ReturnApiController;
use App\Http\Controllers\Api\ScanApiController;
use App\Http\Controllers\Api\ShipmentApiController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────
Route::post('login', [AuthController::class, 'login']);

// ── Authenticated (Sanctum) ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('dashboard', [DashboardApiController::class, 'index']);

    // Orders
    Route::get('orders', [OrderApiController::class, 'index']);
    Route::get('orders/stats', [OrderApiController::class, 'stats']);
    Route::get('orders/{order}', [OrderApiController::class, 'show']);
    Route::post('orders/{order}/status', [OrderApiController::class, 'changeStatus']);

    // Inventory
    Route::get('inventory', [InventoryApiController::class, 'index']);
    Route::get('inventory/alerts', [InventoryApiController::class, 'alerts']);
    Route::get('inventory/quick-check', [InventoryApiController::class, 'quickCheck']);
    Route::get('inventory/{item}', [InventoryApiController::class, 'show']);
    Route::post('inventory/adjust', [InventoryApiController::class, 'adjust']);

    // Scanner
    Route::post('scan', [ScanApiController::class, 'processScan']);
    Route::get('scan/recent', [ScanApiController::class, 'recentScans']);

    // Shipments
    Route::get('shipments', [ShipmentApiController::class, 'index']);
    Route::get('shipments/{shipment}', [ShipmentApiController::class, 'show']);
    Route::post('shipments/{shipment}/dispatch', [ShipmentApiController::class, 'dispatch']);

    // Returns
    Route::get('returns', [ReturnApiController::class, 'index']);
    Route::get('returns/{returnOrder}', [ReturnApiController::class, 'show']);
    Route::post('returns/{returnOrder}/receive', [ReturnApiController::class, 'receive']);

    // Notifications
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('notifications/{notification}/read', [NotificationApiController::class, 'markRead']);
    Route::post('notifications/mark-all-read', [NotificationApiController::class, 'markAllRead']);

    // AI
    Route::get('ai/suggestions', [AiApiController::class, 'suggestions']);
    Route::post('ai/suggestions/{suggestion}/accept', [AiApiController::class, 'acceptSuggestion']);
    Route::post('ai/suggestions/{suggestion}/reject', [AiApiController::class, 'rejectSuggestion']);
    Route::get('ai/stockout-risk', [AiApiController::class, 'stockoutRisk']);
    Route::get('ai/anomalies', [AiApiController::class, 'anomalies']);

    // Analytics
    Route::get('analytics/dashboard', [AnalyticsApiController::class, 'dashboard']);
    Route::get('analytics/designs', [AnalyticsApiController::class, 'designPerformance']);
    Route::get('analytics/profit-trend', [AnalyticsApiController::class, 'profitTrend']);
});
