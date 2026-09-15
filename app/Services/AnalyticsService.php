<?php

namespace App\Services;

use App\Models\Design;
use App\Models\DesignPerformance;
use App\Models\InventoryItem;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePerformance;
use App\Models\Order;
use App\Models\ProfitSnapshot;
use App\Models\PurchaseOrder;
use App\Models\ReturnOrder;
use App\Models\Settlement;
use App\Models\SettlementLine;
use App\Models\Sku;
use App\Models\SubOrder;
use App\Models\Vendor;
use App\Models\VendorPerformance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ── Daily Profit Calculation ────────────────────────────────────

    public function calculateDailyProfit(int $companyId, Carbon $date): ProfitSnapshot
    {
        $orders = Order::forCompany($companyId)
            ->whereDate('order_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $orderIds = $orders->pluck('id');

        // Revenue from sub-orders
        $subOrders = SubOrder::whereIn('order_id', $orderIds)->get();
        $totalRevenue = $subOrders->sum('line_total');
        $totalUnits = $subOrders->sum('quantity');

        // Cost of goods from SKUs
        $skuIds = $subOrders->pluck('sku_id')->filter()->unique();
        $skuCosts = Sku::whereIn('id', $skuIds)->pluck('cost_price', 'id');
        $totalCogs = $subOrders->sum(function ($so) use ($skuCosts) {
            return ($skuCosts[$so->sku_id] ?? 0) * $so->quantity;
        });

        // Settlement data for the date
        $settlementLines = SettlementLine::whereHas('settlement', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->whereIn('order_id', $orderIds)->get();

        $totalCommission = $settlementLines->sum('marketplace_commission');
        $totalShipping = $settlementLines->sum('shipping_fee');
        $totalPenalties = $settlementLines->sum('penalty_amount');
        $totalOtherExpenses = $settlementLines->sum('other_deductions');

        // Returns for the day
        $returns = ReturnOrder::forCompany($companyId)
            ->whereDate('initiated_at', $date)
            ->get();
        $totalReturnsCost = $returns->count() > 0
            ? SubOrder::whereIn('id', $returns->pluck('sub_order_id')->filter())->sum('line_total')
            : 0;

        $returnOrders = Order::forCompany($companyId)
            ->whereDate('order_date', $date)
            ->where('status', 'return')
            ->count();
        $rtoOrders = Order::forCompany($companyId)
            ->whereDate('order_date', $date)
            ->where('status', 'rto')
            ->count();

        $totalOrders = $orders->count();
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalCommission - $totalShipping - $totalReturnsCost - $totalPenalties - $totalOtherExpenses;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
        $returnRate = $totalOrders > 0 ? round(($returnOrders / $totalOrders) * 100, 2) : 0;
        $rtoRate = $totalOrders > 0 ? round(($rtoOrders / $totalOrders) * 100, 2) : 0;

        return ProfitSnapshot::updateOrCreate(
            [
                'company_id'  => $companyId,
                'period_type' => 'daily',
                'period_date' => $date->toDateString(),
            ],
            [
                'total_revenue'                => $totalRevenue,
                'total_cost_of_goods'          => $totalCogs,
                'total_marketplace_commission' => $totalCommission,
                'total_shipping_cost'          => $totalShipping,
                'total_returns_cost'           => $totalReturnsCost,
                'total_penalties'              => $totalPenalties,
                'total_other_expenses'         => $totalOtherExpenses,
                'gross_profit'                 => $grossProfit,
                'net_profit'                   => $netProfit,
                'profit_margin'                => $profitMargin,
                'total_orders'                 => $totalOrders,
                'total_units'                  => $totalUnits,
                'average_order_value'          => $avgOrderValue,
                'return_rate'                  => $returnRate,
                'rto_rate'                     => $rtoRate,
            ]
        );
    }

    // ── Design Performance ─────────────────────────────────────────

    public function calculateDesignPerformance(int $companyId, Carbon $date): Collection
    {
        $designs = Design::forCompany($companyId)->active()->get();
        $results = collect();

        foreach ($designs as $design) {
            // Get all SKU IDs for this design
            $skuIds = Sku::whereHas('variant.product', function ($q) use ($design) {
                $q->where('design_id', $design->id);
            })->pluck('id');

            // Sub-orders sold today for these SKUs
            $subOrders = SubOrder::whereIn('sku_id', $skuIds)
                ->whereHas('order', function ($q) use ($companyId, $date) {
                    $q->forCompany($companyId)
                        ->whereDate('order_date', $date)
                        ->whereNotIn('status', ['cancelled']);
                })
                ->get();

            $unitsSold = $subOrders->sum('quantity');
            $revenue = $subOrders->sum('line_total');

            // Cost
            $skuCosts = Sku::whereIn('id', $skuIds)->pluck('cost_price', 'id');
            $cost = $subOrders->sum(function ($so) use ($skuCosts) {
                return ($skuCosts[$so->sku_id] ?? 0) * $so->quantity;
            });

            // Returns
            $unitsReturned = ReturnOrder::forCompany($companyId)
                ->whereDate('initiated_at', $date)
                ->whereHas('subOrder', function ($q) use ($skuIds) {
                    $q->whereIn('sku_id', $skuIds);
                })
                ->count();

            $profit = $revenue - $cost;
            $profitMargin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;
            $returnRate = $unitsSold > 0 ? round(($unitsReturned / $unitsSold) * 100, 2) : 0;
            $avgSellingPrice = $unitsSold > 0 ? round($revenue / $unitsSold, 2) : 0;

            // Current stock
            $stockRemaining = InventoryItem::whereIn('sku_id', $skuIds)->sum('available_stock');

            // Days of stock based on avg daily sales (last 30 days)
            $avgDailySales = SubOrder::whereIn('sku_id', $skuIds)
                ->whereHas('order', function ($q) use ($companyId, $date) {
                    $q->forCompany($companyId)
                        ->whereBetween('order_date', [$date->copy()->subDays(30), $date])
                        ->whereNotIn('status', ['cancelled']);
                })
                ->sum('quantity') / 30;
            $daysOfStock = $avgDailySales > 0 ? (int) round($stockRemaining / $avgDailySales) : 999;

            $perf = DesignPerformance::updateOrCreate(
                [
                    'company_id'  => $companyId,
                    'design_id'   => $design->id,
                    'period_type' => 'daily',
                    'period_date' => $date->toDateString(),
                ],
                [
                    'units_sold'        => $unitsSold,
                    'units_returned'    => $unitsReturned,
                    'revenue'           => $revenue,
                    'cost'              => $cost,
                    'profit'            => $profit,
                    'profit_margin'     => $profitMargin,
                    'return_rate'       => $returnRate,
                    'avg_selling_price' => $avgSellingPrice,
                    'stock_remaining'   => $stockRemaining,
                    'days_of_stock'     => min($daysOfStock, 999),
                ]
            );

            $results->push($perf);
        }

        return $results;
    }

    // ── Marketplace Performance ────────────────────────────────────

    public function calculateMarketplacePerformance(int $companyId, Carbon $date): Collection
    {
        $accounts = MarketplaceAccount::where('company_id', $companyId)->active()->get();
        $results = collect();

        foreach ($accounts as $account) {
            $orders = Order::forCompany($companyId)
                ->where('marketplace_account_id', $account->id)
                ->whereDate('order_date', $date)
                ->whereNotIn('status', ['cancelled'])
                ->get();

            $totalOrders = $orders->count();
            $totalRevenue = $orders->sum('total_amount');

            // Commission from settlement lines
            $orderIds = $orders->pluck('id');
            $settlementLines = SettlementLine::whereIn('order_id', $orderIds)->get();
            $totalCommission = $settlementLines->sum('marketplace_commission');
            $commissionRate = $totalRevenue > 0 ? round(($totalCommission / $totalRevenue) * 100, 2) : 0;

            // Returns and RTO
            $totalReturns = $orders->where('status', 'return')->count();
            $totalRto = $orders->where('status', 'rto')->count();
            $returnRate = $totalOrders > 0 ? round(($totalReturns / $totalOrders) * 100, 2) : 0;
            $rtoRate = $totalOrders > 0 ? round(($totalRto / $totalOrders) * 100, 2) : 0;

            // Net profit (simplified: revenue - commission - shipping)
            $totalShipping = $settlementLines->sum('shipping_fee');
            $totalPenalties = $settlementLines->sum('penalty_amount');
            $netProfit = $totalRevenue - $totalCommission - $totalShipping - $totalPenalties;

            // Average delivery days (from orders that are delivered)
            $deliveredOrders = Order::forCompany($companyId)
                ->where('marketplace_account_id', $account->id)
                ->where('status', 'delivered')
                ->whereDate('order_date', $date)
                ->get();
            $avgDeliveryDays = 0;
            if ($deliveredOrders->count() > 0) {
                $totalDays = $deliveredOrders->sum(function ($order) {
                    $shipped = $order->statusHistory()
                        ->where('to_status', 'handed_over')
                        ->value('changed_at');
                    $delivered = $order->statusHistory()
                        ->where('to_status', 'delivered')
                        ->value('changed_at');
                    if ($shipped && $delivered) {
                        return Carbon::parse($shipped)->diffInDays(Carbon::parse($delivered));
                    }
                    return 0;
                });
                $avgDeliveryDays = round($totalDays / $deliveredOrders->count(), 1);
            }

            $perf = MarketplacePerformance::updateOrCreate(
                [
                    'company_id'           => $companyId,
                    'marketplace_account_id' => $account->id,
                    'period_type'          => 'daily',
                    'period_date'          => $date->toDateString(),
                ],
                [
                    'total_orders'      => $totalOrders,
                    'total_revenue'     => $totalRevenue,
                    'total_commission'  => $totalCommission,
                    'commission_rate'   => $commissionRate,
                    'total_returns'     => $totalReturns,
                    'return_rate'       => $returnRate,
                    'total_rto'         => $totalRto,
                    'rto_rate'          => $rtoRate,
                    'net_profit'        => $netProfit,
                    'avg_delivery_days' => $avgDeliveryDays,
                ]
            );

            $results->push($perf);
        }

        return $results;
    }

    // ── Vendor Performance ─────────────────────────────────────────

    public function calculateVendorPerformance(int $companyId, Carbon $date): Collection
    {
        $vendors = Vendor::forCompany($companyId)->active()->get();
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        $results = collect();

        foreach ($vendors as $vendor) {
            $pos = PurchaseOrder::forCompany($companyId)
                ->where('vendor_id', $vendor->id)
                ->whereBetween('order_date', [$monthStart, $monthEnd])
                ->get();

            $totalPos = $pos->count();
            $totalUnitsOrdered = $pos->sum(function ($po) {
                return $po->lines->sum('quantity');
            });
            $totalUnitsReceived = $pos->sum(function ($po) {
                return $po->lines->sum('received_quantity');
            });

            $fulfillmentRate = $totalUnitsOrdered > 0
                ? round(($totalUnitsReceived / $totalUnitsOrdered) * 100, 2)
                : 0;

            // Average lead time
            $receivedPos = $pos->filter(fn ($po) => $po->isReceived());
            $avgLeadTime = 0;
            if ($receivedPos->count() > 0) {
                $totalLeadTime = $receivedPos->sum(function ($po) {
                    $lastReceipt = $po->receipts()->latest()->value('received_at');
                    if ($lastReceipt && $po->order_date) {
                        return Carbon::parse($po->order_date)->diffInDays(Carbon::parse($lastReceipt));
                    }
                    return $vendor->lead_time_days ?? 0;
                });
                $avgLeadTime = round($totalLeadTime / $receivedPos->count(), 1);
            }

            $totalAmount = $pos->sum('total_amount');

            // On-time delivery rate
            $onTimeCount = $receivedPos->filter(function ($po) {
                if (!$po->expected_delivery_date) return true;
                $lastReceipt = $po->receipts()->latest()->value('received_at');
                return $lastReceipt && Carbon::parse($lastReceipt)->lte($po->expected_delivery_date);
            })->count();
            $onTimeRate = $receivedPos->count() > 0
                ? round(($onTimeCount / $receivedPos->count()) * 100, 2)
                : 0;

            $perf = VendorPerformance::updateOrCreate(
                [
                    'company_id'  => $companyId,
                    'vendor_id'   => $vendor->id,
                    'period_type' => 'monthly',
                    'period_date' => $monthStart->toDateString(),
                ],
                [
                    'total_pos'            => $totalPos,
                    'total_units_ordered'  => $totalUnitsOrdered,
                    'total_units_received' => $totalUnitsReceived,
                    'fulfillment_rate'     => $fulfillmentRate,
                    'avg_lead_time_days'   => $avgLeadTime,
                    'total_amount'         => $totalAmount,
                    'quality_return_rate'  => 0, // Requires quality inspection data
                    'on_time_delivery_rate' => $onTimeRate,
                ]
            );

            $results->push($perf);
        }

        return $results;
    }

    // ── Dashboard Data ─────────────────────────────────────────────

    public function getDashboardData(int $companyId): array
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $monthStart = $today->copy()->startOfMonth();
        $prevMonthStart = $today->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $today->copy()->subMonth()->endOfMonth();

        // Today's metrics (live calculation)
        $todayOrders = Order::forCompany($companyId)
            ->whereDate('order_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->get();
        $todayRevenue = $todayOrders->sum('total_amount');
        $todayOrderCount = $todayOrders->count();

        // This week metrics from snapshots or live
        $weekSnapshot = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($weekStart, $today)
            ->get();
        $weekRevenue = $weekSnapshot->sum('total_revenue') ?: Order::forCompany($companyId)
            ->whereBetween('order_date', [$weekStart, $today])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');
        $weekOrders = $weekSnapshot->sum('total_orders') ?: Order::forCompany($companyId)
            ->whereBetween('order_date', [$weekStart, $today])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // This month
        $monthSnapshot = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($monthStart, $today)
            ->get();
        $monthRevenue = $monthSnapshot->sum('total_revenue') ?: Order::forCompany($companyId)
            ->whereBetween('order_date', [$monthStart, $today])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');
        $monthOrders = $monthSnapshot->sum('total_orders') ?: Order::forCompany($companyId)
            ->whereBetween('order_date', [$monthStart, $today])
            ->whereNotIn('status', ['cancelled'])
            ->count();
        $monthProfit = $monthSnapshot->sum('net_profit');
        $monthProfitMargin = $monthRevenue > 0 ? round(($monthProfit / $monthRevenue) * 100, 2) : 0;

        // Previous month for comparison
        $prevMonthSnapshot = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($prevMonthStart, $prevMonthEnd)
            ->get();
        $prevMonthRevenue = $prevMonthSnapshot->sum('total_revenue');
        $prevMonthProfit = $prevMonthSnapshot->sum('net_profit');

        // Top 5 designs by profit (this month)
        $topDesigns = DesignPerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('design_id')
            ->selectRaw('SUM(units_sold) as total_units')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->selectRaw('SUM(profit) as total_profit')
            ->selectRaw('CASE WHEN SUM(revenue) > 0 THEN ROUND((SUM(profit) / SUM(revenue)) * 100, 2) ELSE 0 END as margin')
            ->groupBy('design_id')
            ->orderByDesc('total_profit')
            ->limit(5)
            ->with('design')
            ->get();

        // Top 5 designs by return rate (problematic)
        $problemDesigns = DesignPerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('design_id')
            ->selectRaw('SUM(units_sold) as total_units')
            ->selectRaw('SUM(units_returned) as total_returned')
            ->selectRaw('CASE WHEN SUM(units_sold) > 0 THEN ROUND((SUM(units_returned) / SUM(units_sold)) * 100, 2) ELSE 0 END as return_pct')
            ->groupBy('design_id')
            ->havingRaw('SUM(units_sold) > 0')
            ->orderByDesc('return_pct')
            ->limit(5)
            ->get();

        // Marketplace comparison
        $marketplaceData = MarketplacePerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('marketplace_account_id')
            ->selectRaw('SUM(total_orders) as orders')
            ->selectRaw('SUM(total_revenue) as revenue')
            ->selectRaw('SUM(total_commission) as commission')
            ->selectRaw('CASE WHEN SUM(total_revenue) > 0 THEN ROUND((SUM(total_commission) / SUM(total_revenue)) * 100, 2) ELSE 0 END as commission_pct')
            ->selectRaw('SUM(total_returns) as returns')
            ->selectRaw('CASE WHEN SUM(total_orders) > 0 THEN ROUND((SUM(total_returns) / SUM(total_orders)) * 100, 2) ELSE 0 END as return_pct')
            ->selectRaw('SUM(net_profit) as net_profit')
            ->groupBy('marketplace_account_id')
            ->get();

        // Inventory alerts
        $lowStockCount = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->where('inventory_items.available_stock', '>', 0)
            ->count();
        $outOfStockCount = InventoryItem::where('available_stock', '<=', 0)->count();

        // Pending actions
        $unmatchedSettlements = SettlementLine::where('match_status', 'unmatched')->count();
        $pendingPOs = PurchaseOrder::forCompany($companyId)->pending()->count();
        $pendingReturns = ReturnOrder::forCompany($companyId)
            ->whereIn('status', ['received', 'inspecting'])
            ->count();
        $pendingActions = $unmatchedSettlements + $pendingPOs + $pendingReturns;

        return [
            'today' => [
                'revenue' => $todayRevenue,
                'orders'  => $todayOrderCount,
            ],
            'week' => [
                'revenue' => $weekRevenue,
                'orders'  => $weekOrders,
            ],
            'month' => [
                'revenue'       => $monthRevenue,
                'orders'        => $monthOrders,
                'profit'        => $monthProfit,
                'profit_margin' => $monthProfitMargin,
            ],
            'prev_month' => [
                'revenue' => $prevMonthRevenue,
                'profit'  => $prevMonthProfit,
            ],
            'top_designs'      => $topDesigns,
            'problem_designs'  => $problemDesigns,
            'marketplace_data' => $marketplaceData,
            'inventory_alerts' => [
                'low_stock'    => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
            'pending_actions' => [
                'total'                  => $pendingActions,
                'unmatched_settlements'  => $unmatchedSettlements,
                'pending_pos'            => $pendingPOs,
                'pending_returns'        => $pendingReturns,
            ],
        ];
    }

    // ── Profit Trend ───────────────────────────────────────────────

    public function getProfitTrend(int $companyId, string $period = 'daily', int $count = 30): Collection
    {
        return ProfitSnapshot::forCompany($companyId)
            ->period($period)
            ->orderByDesc('period_date')
            ->limit($count)
            ->get()
            ->reverse()
            ->values();
    }

    // ── Design Ranking ─────────────────────────────────────────────

    public function getDesignRanking(int $companyId, string $sortBy = 'profit', string $period = 'monthly'): Collection
    {
        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        $query = DesignPerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('design_id')
            ->selectRaw('SUM(units_sold) as total_units')
            ->selectRaw('SUM(units_returned) as total_returned')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->selectRaw('SUM(cost) as total_cost')
            ->selectRaw('SUM(profit) as total_profit')
            ->selectRaw('CASE WHEN SUM(revenue) > 0 THEN ROUND((SUM(profit) / SUM(revenue)) * 100, 2) ELSE 0 END as margin')
            ->selectRaw('CASE WHEN SUM(units_sold) > 0 THEN ROUND((SUM(units_returned) / SUM(units_sold)) * 100, 2) ELSE 0 END as return_pct')
            ->selectRaw('MAX(stock_remaining) as stock')
            ->selectRaw('MAX(days_of_stock) as days_stock')
            ->groupBy('design_id');

        $orderColumn = match ($sortBy) {
            'revenue'     => 'total_revenue',
            'units'       => 'total_units',
            'return_rate' => 'return_pct',
            'stock_days'  => 'days_stock',
            default       => 'total_profit',
        };

        return $query->orderByDesc($orderColumn)->get();
    }

    // ── WHY Analysis ───────────────────────────────────────────────

    public function getWhyAnalysis(int $companyId): array
    {
        $today = Carbon::today();
        $thisWeekStart = $today->copy()->startOfWeek();
        $lastWeekStart = $today->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $today->copy()->subWeek()->endOfWeek();
        $monthStart = $today->copy()->startOfMonth();

        // This week vs last week profit comparison
        $thisWeekProfit = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($thisWeekStart, $today)
            ->sum('net_profit');
        $lastWeekProfit = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($lastWeekStart, $lastWeekEnd)
            ->sum('net_profit');

        $profitChange = $lastWeekProfit != 0
            ? round((($thisWeekProfit - $lastWeekProfit) / abs($lastWeekProfit)) * 100, 1)
            : 0;

        // Identify biggest profit change contributor (returns vs commission)
        $thisWeekReturns = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($thisWeekStart, $today)
            ->sum('total_returns_cost');
        $lastWeekReturns = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($lastWeekStart, $lastWeekEnd)
            ->sum('total_returns_cost');
        $returnsChangeAmt = $thisWeekReturns - $lastWeekReturns;

        $thisWeekCommission = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($thisWeekStart, $today)
            ->sum('total_marketplace_commission');
        $lastWeekCommission = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($lastWeekStart, $lastWeekEnd)
            ->sum('total_marketplace_commission');
        $commissionChangeAmt = $thisWeekCommission - $lastWeekCommission;

        // Build profit insight
        $profitInsight = null;
        if ($profitChange < -5) {
            $reason = abs($returnsChangeAmt) > abs($commissionChangeAmt)
                ? 'returns increased by ' . number_format(abs($returnsChangeAmt), 0)
                : 'commission costs increased by ' . number_format(abs($commissionChangeAmt), 0);
            $profitInsight = "Profit dropped " . abs($profitChange) . "% vs last week because {$reason}.";
        } elseif ($profitChange > 5) {
            $profitInsight = "Profit improved " . $profitChange . "% vs last week.";
        }

        // High return rate designs
        $highReturnDesigns = DesignPerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('design_id')
            ->selectRaw('SUM(units_sold) as total_units')
            ->selectRaw('SUM(units_returned) as total_returned')
            ->selectRaw('CASE WHEN SUM(units_sold) > 0 THEN ROUND((SUM(units_returned) / SUM(units_sold)) * 100, 2) ELSE 0 END as return_pct')
            ->groupBy('design_id')
            ->havingRaw('SUM(units_sold) >= 5')
            ->havingRaw('CASE WHEN SUM(units_sold) > 0 THEN ROUND((SUM(units_returned) / SUM(units_sold)) * 100, 2) ELSE 0 END > 15')
            ->orderByDesc('return_pct')
            ->limit(3)
            ->get();

        // Most profitable marketplace
        $marketplaceRanking = MarketplacePerformance::forCompany($companyId)
            ->period('daily')
            ->dateRange($monthStart, $today)
            ->select('marketplace_account_id')
            ->selectRaw('SUM(net_profit) as total_net_profit')
            ->selectRaw('SUM(total_revenue) as total_revenue')
            ->groupBy('marketplace_account_id')
            ->orderByDesc('total_net_profit')
            ->get();

        $insights = [];

        if ($profitInsight) {
            $insights[] = [
                'type'    => $profitChange < 0 ? 'warning' : 'success',
                'title'   => $profitChange < 0 ? 'Profit Dropped' : 'Profit Improved',
                'message' => $profitInsight,
            ];
        }

        if ($highReturnDesigns->count() > 0) {
            $designNames = $highReturnDesigns->map(function ($d) {
                $design = Design::find($d->design_id);
                return ($design ? $design->name : "Design #{$d->design_id}") . " ({$d->return_pct}%)";
            })->implode(', ');
            $insights[] = [
                'type'    => 'danger',
                'title'   => 'High Return Designs',
                'message' => "These designs have return rates above 15%: {$designNames}. Consider quality checks or listing review.",
            ];
        }

        if ($marketplaceRanking->count() > 1) {
            $best = $marketplaceRanking->first();
            $bestAccount = MarketplaceAccount::find($best->marketplace_account_id);
            $insights[] = [
                'type'    => 'info',
                'title'   => 'Best Marketplace',
                'message' => ($bestAccount->account_name ?? 'Unknown') . " is the most profitable marketplace this month with net profit of " . number_format($best->total_net_profit, 0) . ".",
            ];
        }

        return $insights;
    }

    // ── P&L Data ───────────────────────────────────────────────────

    public function getProfitAndLoss(int $companyId, Carbon $from, Carbon $to): array
    {
        $snapshots = ProfitSnapshot::forCompany($companyId)
            ->daily()
            ->dateRange($from, $to)
            ->get();

        return [
            'revenue'    => $snapshots->sum('total_revenue'),
            'cogs'       => $snapshots->sum('total_cost_of_goods'),
            'gross_profit' => $snapshots->sum('gross_profit'),
            'commission' => $snapshots->sum('total_marketplace_commission'),
            'shipping'   => $snapshots->sum('total_shipping_cost'),
            'returns'    => $snapshots->sum('total_returns_cost'),
            'penalties'  => $snapshots->sum('total_penalties'),
            'other'      => $snapshots->sum('total_other_expenses'),
            'net_profit' => $snapshots->sum('net_profit'),
            'orders'     => $snapshots->sum('total_orders'),
            'units'      => $snapshots->sum('total_units'),
        ];
    }

    // ── Inventory Analytics ────────────────────────────────────────

    public function getInventoryAnalytics(int $companyId): array
    {
        $totalSkus = Sku::forCompany($companyId)->count();
        $totalUnits = InventoryItem::forCompany($companyId)->sum('available_stock');
        $stockValue = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->sum(DB::raw('inventory_items.available_stock * skus.cost_price'));

        // Dead stock: SKUs with stock but no sales in 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $soldSkuIds = SubOrder::whereHas('order', function ($q) use ($companyId, $thirtyDaysAgo) {
            $q->forCompany($companyId)
                ->where('order_date', '>=', $thirtyDaysAgo)
                ->whereNotIn('status', ['cancelled']);
        })->distinct()->pluck('sku_id')->filter();

        $deadStockCount = InventoryItem::forCompany($companyId)
            ->where('available_stock', '>', 0)
            ->whereNotIn('sku_id', $soldSkuIds)
            ->count();

        // Fast movers: top 10 SKUs by sales volume in last 30 days
        $fastMovers = SubOrder::whereHas('order', function ($q) use ($companyId, $thirtyDaysAgo) {
            $q->forCompany($companyId)
                ->where('order_date', '>=', $thirtyDaysAgo)
                ->whereNotIn('status', ['cancelled']);
        })
            ->select('sku_id')
            ->selectRaw('SUM(quantity) as total_sold')
            ->whereNotNull('sku_id')
            ->groupBy('sku_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Slow movers: bottom 10 SKUs with stock but lowest sales
        $slowMovers = DB::table('inventory_items')
            ->leftJoin(DB::raw("(SELECT sku_id, SUM(quantity) as total_sold FROM sub_orders
                JOIN orders ON orders.id = sub_orders.order_id
                WHERE orders.company_id = {$companyId}
                AND orders.order_date >= '{$thirtyDaysAgo->toDateString()}'
                AND orders.status != 'cancelled'
                GROUP BY sku_id) as sales"), 'inventory_items.sku_id', '=', 'sales.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('inventory_items.available_stock', '>', 0)
            ->select('inventory_items.sku_id', 'inventory_items.available_stock', DB::raw('COALESCE(sales.total_sold, 0) as total_sold'))
            ->orderBy('total_sold')
            ->limit(10)
            ->get();

        // Reorder alerts
        $reorderAlerts = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->select('inventory_items.sku_id', 'skus.sku_code', 'inventory_items.available_stock', 'skus.minimum_stock_level')
            ->orderBy('inventory_items.available_stock')
            ->get();

        // Stock health distribution
        $goodStock = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '>', DB::raw('skus.minimum_stock_level * 2'))
            ->count();
        $warningStock = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', DB::raw('skus.minimum_stock_level * 2'))
            ->whereColumn('inventory_items.available_stock', '>', 'skus.minimum_stock_level')
            ->count();
        $criticalStock = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->count();

        return [
            'total_skus'      => $totalSkus,
            'total_units'     => $totalUnits,
            'stock_value'     => $stockValue,
            'dead_stock'      => $deadStockCount,
            'fast_movers'     => $fastMovers,
            'slow_movers'     => $slowMovers,
            'reorder_alerts'  => $reorderAlerts,
            'stock_health'    => [
                'good'     => $goodStock,
                'warning'  => $warningStock,
                'critical' => $criticalStock,
            ],
        ];
    }
}
