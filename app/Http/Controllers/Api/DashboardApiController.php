<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\InventoryItem;
use App\Models\ReturnOrder;
use App\Models\PurchaseOrder;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AnalyticsService $analyticsService,
    ) {}

    /**
     * Return dashboard KPIs for the mobile app.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $today = now()->toDateString();

        // Today's orders
        $todayOrders = Order::forCompany($companyId)
            ->whereDate('order_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $todayRevenue = (float) $todayOrders->sum('total_amount');
        $todayOrderCount = $todayOrders->count();

        // Pending actions
        $pendingOrders = Order::forCompany($companyId)
            ->whereIn('status', [Order::STATUS_NEW, Order::STATUS_ACCEPTED])
            ->count();
        $pendingReturns = ReturnOrder::forCompany($companyId)
            ->whereIn('status', ['received', 'inspecting'])
            ->count();

        // Low stock alerts
        $lowStockCount = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->where('inventory_items.available_stock', '>', 0)
            ->count();
        $outOfStockCount = InventoryItem::where('company_id', $companyId)
            ->where('available_stock', '<=', 0)
            ->count();

        // Recent activity (last 10 orders)
        $recentOrders = Order::forCompany($companyId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($o) => [
                'id'                   => $o->id,
                'marketplace_order_id' => $o->marketplace_order_id,
                'status'               => $o->status,
                'status_label'         => $o->status_label,
                'total_amount'         => (float) $o->total_amount,
                'customer_name'        => $o->customer_name,
                'created_at'           => $o->created_at?->toIso8601String(),
            ]);

        // Recent notifications
        $recentNotifications = AppNotification::forUser($request->user()->id)
            ->unread()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id'      => $n->id,
                'title'   => $n->title,
                'message' => $n->message,
                'type'    => $n->type,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'today' => [
                'orders'  => $todayOrderCount,
                'revenue' => $todayRevenue,
            ],
            'pending_actions' => [
                'orders'  => $pendingOrders,
                'returns' => $pendingReturns,
                'total'   => $pendingOrders + $pendingReturns,
            ],
            'inventory_alerts' => [
                'low_stock'    => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
            'recent_orders'        => $recentOrders,
            'recent_notifications' => $recentNotifications,
        ], 'Dashboard loaded.');
    }
}
