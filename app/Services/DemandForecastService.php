<?php

namespace App\Services;

use App\Models\Design;
use App\Models\InventoryItem;
use App\Models\Sku;
use App\Models\SubOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemandForecastService
{
    /**
     * Forecast demand for a design using simple moving average.
     */
    public function forecastDesign(int $companyId, int $designId, int $daysAhead = 7): array
    {
        $lookback = 30;
        $startDate = now()->subDays($lookback);

        // Get daily order counts for this design
        $dailyOrders = SubOrder::forCompany($companyId)
            ->whereHas('sku.variant.product', function ($q) use ($designId) {
                $q->where('design_id', $designId);
            })
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('order_date', '>=', $startDate);
            })
            ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
            ->select(
                DB::raw('DATE(orders.order_date) as date'),
                DB::raw('SUM(sub_orders.quantity) as total_qty')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total_qty', 'date')
            ->toArray();

        // Fill in zero-days
        $allDays = [];
        for ($i = $lookback; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $allDays[$date] = (int) ($dailyOrders[$date] ?? 0);
        }

        $values = array_values($allDays);
        $totalUnits = array_sum($values);
        $activeDays = count($values);
        $avgDaily = $activeDays > 0 ? $totalUnits / $activeDays : 0;

        // Simple moving average — last 7 days for trend
        $last7 = array_slice($values, -7);
        $recentAvg = count($last7) > 0 ? array_sum($last7) / count($last7) : 0;

        $prev7 = array_slice($values, -14, 7);
        $prevAvg = count($prev7) > 0 ? array_sum($prev7) / count($prev7) : 0;

        // Trend direction
        if ($prevAvg > 0) {
            $trendPct = (($recentAvg - $prevAvg) / $prevAvg) * 100;
        } else {
            $trendPct = $recentAvg > 0 ? 100 : 0;
        }

        $trend = $trendPct > 10 ? 'up' : ($trendPct < -10 ? 'down' : 'stable');

        // Current stock for this design
        $currentStock = InventoryItem::forCompany($companyId)
            ->whereHas('sku.variant.product', function ($q) use ($designId) {
                $q->where('design_id', $designId);
            })
            ->sum('available_stock');

        // Days until stockout
        $daysUntilStockout = $recentAvg > 0 ? floor($currentStock / $recentAvg) : ($currentStock > 0 ? 999 : 0);

        // Suggested reorder quantity (7 days of stock + safety buffer of 3 days)
        $suggestedReorder = max(0, ceil($recentAvg * ($daysAhead + 3)) - $currentStock);

        // Forecast for each day ahead
        $forecast = [];
        for ($i = 1; $i <= $daysAhead; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $forecast[] = [
                'date'          => $date,
                'predicted_qty' => round($recentAvg, 1),
            ];
        }

        $design = Design::find($designId);

        return [
            'design_id'          => $designId,
            'design_name'        => $design?->name ?? "Design #{$designId}",
            'daily_run_rate'     => round($recentAvg, 1),
            'avg_daily_30d'      => round($avgDaily, 1),
            'current_stock'      => $currentStock,
            'days_until_stockout' => min($daysUntilStockout, 999),
            'suggested_reorder'  => $suggestedReorder,
            'trend'              => $trend,
            'trend_pct'          => round($trendPct, 1),
            'forecast'           => $forecast,
            'daily_history'      => $allDays,
        ];
    }

    /**
     * Calculate reorder point for a specific SKU.
     */
    public function calculateReorderPoint(int $companyId, int $skuId): array
    {
        $lookback = 30;
        $startDate = now()->subDays($lookback);

        // Daily demand
        $totalDemand = SubOrder::forCompany($companyId)
            ->where('sku_id', $skuId)
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('order_date', '>=', $startDate);
            })
            ->sum('quantity');

        $avgDaily = $totalDemand / $lookback;

        // Get current stock
        $inventory = InventoryItem::forCompany($companyId)
            ->where('sku_id', $skuId)
            ->first();

        $currentStock = $inventory?->available_stock ?? 0;

        // Lead time assumption: 5 days (average PO fulfillment)
        $leadTimeDays = 5;
        $safetyStock = ceil($avgDaily * 3); // 3 days safety
        $reorderPoint = ceil(($avgDaily * $leadTimeDays) + $safetyStock);
        $reorderQty = ceil($avgDaily * 14); // 2 weeks worth

        $sku = Sku::with('variant.product.design')->find($skuId);

        return [
            'sku_id'        => $skuId,
            'sku_code'      => $sku?->sku_code ?? '',
            'sku_name'      => $sku?->short_name ?? '',
            'avg_daily'     => round($avgDaily, 1),
            'current_stock' => $currentStock,
            'reorder_point' => $reorderPoint,
            'reorder_qty'   => max($reorderQty, 1),
            'safety_stock'  => $safetyStock,
            'lead_time'     => $leadTimeDays,
            'needs_reorder' => $currentStock <= $reorderPoint,
            'days_of_stock' => $avgDaily > 0 ? floor($currentStock / $avgDaily) : ($currentStock > 0 ? 999 : 0),
        ];
    }

    /**
     * Get SKUs at risk of stockout within the given days.
     */
    public function getStockoutRisk(int $companyId, int $withinDays = 7): array
    {
        $startDate = now()->subDays(30);
        $risks = [];

        // Get all inventory items with stock
        $inventoryItems = InventoryItem::forCompany($companyId)
            ->where('available_stock', '>', 0)
            ->with('sku.variant.product.design')
            ->get();

        foreach ($inventoryItems as $inv) {
            $totalDemand = SubOrder::forCompany($companyId)
                ->where('sku_id', $inv->sku_id)
                ->whereHas('order', function ($q) use ($startDate) {
                    $q->where('order_date', '>=', $startDate);
                })
                ->sum('quantity');

            $avgDaily = $totalDemand / 30;

            if ($avgDaily > 0) {
                $daysOfStock = floor($inv->available_stock / $avgDaily);

                if ($daysOfStock <= $withinDays) {
                    $risks[] = [
                        'sku_id'        => $inv->sku_id,
                        'sku_code'      => $inv->sku?->sku_code ?? '',
                        'sku_name'      => $inv->sku?->short_name ?? '',
                        'current_stock' => $inv->available_stock,
                        'avg_daily'     => round($avgDaily, 1),
                        'days_of_stock' => $daysOfStock,
                        'urgency'       => $daysOfStock <= 2 ? 'critical' : ($daysOfStock <= 4 ? 'high' : 'medium'),
                    ];
                }
            }
        }

        // Sort by days of stock ascending (most urgent first)
        usort($risks, fn ($a, $b) => $a['days_of_stock'] <=> $b['days_of_stock']);

        return $risks;
    }

    /**
     * Get basic seasonal trend for a design.
     */
    public function getSeasonalTrend(int $companyId, int $designId): array
    {
        // Weekly aggregation for last 12 weeks
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $qty = SubOrder::forCompany($companyId)
                ->whereHas('sku.variant.product', function ($q) use ($designId) {
                    $q->where('design_id', $designId);
                })
                ->whereHas('order', function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('order_date', [$weekStart, $weekEnd]);
                })
                ->sum('quantity');

            $weeks[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_label' => $weekStart->format('d M'),
                'quantity'   => (int) $qty,
            ];
        }

        $quantities = array_column($weeks, 'quantity');
        $avgQty = count($quantities) > 0 ? array_sum($quantities) / count($quantities) : 0;
        $peakWeek = $quantities ? max($quantities) : 0;
        $lowWeek = $quantities ? min($quantities) : 0;

        return [
            'weeks'    => $weeks,
            'avg_weekly' => round($avgQty, 1),
            'peak'     => $peakWeek,
            'low'      => $lowWeek,
            'variance' => $avgQty > 0 ? round((($peakWeek - $lowWeek) / $avgQty) * 100, 1) : 0,
        ];
    }
}
