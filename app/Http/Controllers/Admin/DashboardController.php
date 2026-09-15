<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppException;
use App\Models\AuditLog;
use App\Models\Design;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\ReturnOrder;
use App\Models\SettlementLine;
use App\Models\Sku;
use App\Models\SkuMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Greeting
        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default    => 'Good evening',
        };

        // Inventory snapshot
        $totalSkus      = Sku::count();
        $totalStock      = InventoryItem::sum('available_stock');
        $stockValue      = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->sum(DB::raw('inventory_items.available_stock * skus.cost_price'));
        $outOfStockCount = InventoryItem::where('available_stock', '<=', 0)->count();
        $lowStockCount   = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->where('inventory_items.available_stock', '>', 0)
            ->count();

        // Purchase orders
        $pendingPOs   = PurchaseOrder::pending()->count();
        $totalPOs     = PurchaseOrder::active()->count();

        // Exceptions
        $openExceptions = AppException::open()->count();

        // Unmapped SKUs
        $totalMappedSkuIds = SkuMapping::distinct('sku_id')->count('sku_id');
        $unmappedSkus      = max(0, $totalSkus - $totalMappedSkuIds);

        // Recent activity (audit logs)
        $recentLogs = AuditLog::with('user')
            ->latest('created_at')
            ->limit(10)
            ->get();

        // Needs attention items
        $attentionItems = AppException::open()
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->limit(5)
            ->get();

        // Revenue KPIs
        $todayRevenue = Order::whereDate('order_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');
        $weekRevenue = Order::whereBetween('order_date', [now()->startOfWeek(), now()])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');
        $monthRevenue = Order::whereBetween('order_date', [now()->startOfMonth(), now()])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');

        // Pending actions summary
        $unmatchedSettlements = SettlementLine::where('match_status', 'unmatched')->count();
        $pendingReturns = ReturnOrder::whereIn('status', ['received', 'inspecting'])->count();

        return view('admin.dashboard', compact(
            'greeting',
            'totalSkus',
            'totalStock',
            'stockValue',
            'outOfStockCount',
            'lowStockCount',
            'pendingPOs',
            'totalPOs',
            'openExceptions',
            'unmappedSkus',
            'recentLogs',
            'attentionItems',
            'todayRevenue',
            'weekRevenue',
            'monthRevenue',
            'unmatchedSettlements',
            'pendingReturns',
        ));
    }
}
