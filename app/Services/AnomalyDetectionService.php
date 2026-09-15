<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Settlement;
use App\Models\SettlementLine;
use App\Models\SubOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnomalyDetectionService
{
    /**
     * Scan for unusual orders (high quantity, price anomalies).
     */
    public function scanOrderAnomalies(int $companyId): array
    {
        $anomalies = [];

        // 1. Orders with unusually high quantity (> 3x average)
        $avgQty = SubOrder::forCompany($companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('quantity') ?: 1;

        $threshold = max($avgQty * 3, 5);

        $highQtyOrders = SubOrder::forCompany($companyId)
            ->where('quantity', '>', $threshold)
            ->where('created_at', '>=', now()->subDays(7))
            ->with('order')
            ->limit(10)
            ->get();

        foreach ($highQtyOrders as $so) {
            $anomalies[] = [
                'type'        => 'high_quantity',
                'severity'    => 'high',
                'title'       => "Unusually high quantity: {$so->quantity} units",
                'description' => "Order #{$so->order?->marketplace_order_id} has {$so->quantity} units of {$so->product_name}. Average is " . round($avgQty, 1) . " units.",
                'entity_type' => 'order',
                'entity_id'   => $so->order_id,
                'action'      => 'Review this order for accuracy',
            ];
        }

        // 2. Price anomalies — selling below cost
        $belowCostItems = SubOrder::forCompany($companyId)
            ->whereNotNull('sku_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereHas('sku', function ($q) {
                $q->whereColumn('cost_price', '>', DB::raw('0'));
            })
            ->with(['sku', 'order'])
            ->get()
            ->filter(function ($so) {
                return $so->sku && $so->sku->cost_price > 0 && $so->unit_price < $so->sku->cost_price;
            })
            ->take(10);

        foreach ($belowCostItems as $so) {
            $anomalies[] = [
                'type'        => 'price_below_cost',
                'severity'    => 'critical',
                'title'       => "Selling below cost: {$so->product_name}",
                'description' => "Unit price Rs." . number_format($so->unit_price, 2) . " is below cost Rs." . number_format($so->sku->cost_price, 2) . ". Loss per unit: Rs." . number_format($so->sku->cost_price - $so->unit_price, 2),
                'entity_type' => 'sku',
                'entity_id'   => $so->sku_id,
                'action'      => 'Review pricing for this SKU',
            ];
        }

        return $anomalies;
    }

    /**
     * Scan for inventory discrepancies.
     */
    public function scanInventoryAnomalies(int $companyId): array
    {
        $anomalies = [];

        // 1. Negative available stock
        $negativeStock = InventoryItem::forCompany($companyId)
            ->where('available_stock', '<', 0)
            ->with('sku.variant.product.design')
            ->limit(10)
            ->get();

        foreach ($negativeStock as $inv) {
            $skuName = $inv->sku?->sku_code ?? "SKU #{$inv->sku_id}";
            $anomalies[] = [
                'type'        => 'negative_stock',
                'severity'    => 'critical',
                'title'       => "Negative stock: {$skuName}",
                'description' => "Available stock is {$inv->available_stock}. Physical: {$inv->physical_stock}, Reserved: {$inv->reserved_stock}.",
                'entity_type' => 'sku',
                'entity_id'   => $inv->sku_id,
                'action'      => 'Conduct physical count and reconcile',
            ];
        }

        // 2. Reserved stock exceeds physical stock
        $overReserved = InventoryItem::forCompany($companyId)
            ->whereColumn('reserved_stock', '>', 'physical_stock')
            ->where('physical_stock', '>', 0)
            ->with('sku.variant.product.design')
            ->limit(10)
            ->get();

        foreach ($overReserved as $inv) {
            $skuName = $inv->sku?->sku_code ?? "SKU #{$inv->sku_id}";
            $anomalies[] = [
                'type'        => 'over_reserved',
                'severity'    => 'high',
                'title'       => "Over-reserved stock: {$skuName}",
                'description' => "Reserved ({$inv->reserved_stock}) exceeds physical ({$inv->physical_stock}).",
                'entity_type' => 'sku',
                'entity_id'   => $inv->sku_id,
                'action'      => 'Check pending orders and adjust reservations',
            ];
        }

        return $anomalies;
    }

    /**
     * Scan for settlement/payment mismatches.
     */
    public function scanSettlementAnomalies(int $companyId): array
    {
        $anomalies = [];

        // 1. Unmatched settlement lines
        $unmatchedCount = SettlementLine::whereHas('settlement', function ($q) use ($companyId) {
            $q->forCompany($companyId);
        })->where('match_status', 'unmatched')->count();

        if ($unmatchedCount > 0) {
            $anomalies[] = [
                'type'        => 'unmatched_settlements',
                'severity'    => $unmatchedCount > 50 ? 'high' : 'medium',
                'title'       => "{$unmatchedCount} unmatched settlement lines",
                'description' => "There are {$unmatchedCount} settlement lines that haven't been matched to orders. This affects P&L accuracy.",
                'entity_type' => null,
                'entity_id'   => null,
                'action'      => 'Review and match settlement lines',
            ];
        }

        // 2. Settlement amount mismatches > 5%
        $recentSettlements = Settlement::forCompany($companyId)
            ->whereIn('status', ['reconciled', 'partially_reconciled'])
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        foreach ($recentSettlements as $settlement) {
            $lineSum = $settlement->lines()->sum('net_amount');
            if ($settlement->net_payable > 0 && $lineSum > 0) {
                $diff = abs($settlement->net_payable - $lineSum);
                $pct = ($diff / $settlement->net_payable) * 100;

                if ($pct > 5) {
                    $anomalies[] = [
                        'type'        => 'settlement_mismatch',
                        'severity'    => $pct > 15 ? 'critical' : 'high',
                        'title'       => "Settlement #{$settlement->settlement_reference} mismatch: " . round($pct, 1) . '%',
                        'description' => "Expected Rs." . number_format($settlement->net_payable, 2) . " but line items total Rs." . number_format($lineSum, 2) . ". Difference: Rs." . number_format($diff, 2),
                        'entity_type' => 'settlement',
                        'entity_id'   => $settlement->id,
                        'action'      => 'Review settlement reconciliation',
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Scan for return rate spikes.
     */
    public function scanReturnAnomalies(int $companyId): array
    {
        $anomalies = [];

        // Return rate per design in last 30 days vs previous 30 days
        $recentReturns = ReturnOrder::forCompany($companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->with('subOrder')
            ->get();

        // Group by design via sub-order
        $returnsByDesign = $recentReturns->groupBy(function ($ret) {
            return $ret->subOrder?->sku_id;
        })->filter(fn ($group, $key) => $key !== null);

        foreach ($returnsByDesign as $skuId => $returns) {
            $returnCount = $returns->count();

            // Get total orders for this SKU in same period
            $totalOrders = SubOrder::forCompany($companyId)
                ->where('sku_id', $skuId)
                ->whereHas('order', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                })
                ->count();

            if ($totalOrders >= 5) {
                $returnRate = ($returnCount / $totalOrders) * 100;

                if ($returnRate > 20) {
                    $skuCode = $returns->first()->subOrder?->marketplace_sku ?? "SKU #{$skuId}";
                    $anomalies[] = [
                        'type'        => 'high_return_rate',
                        'severity'    => $returnRate > 40 ? 'critical' : 'high',
                        'title'       => "High return rate: {$skuCode} (" . round($returnRate) . '%)',
                        'description' => "{$returnCount} returns out of {$totalOrders} orders in the last 30 days.",
                        'entity_type' => 'sku',
                        'entity_id'   => $skuId,
                        'action'      => 'Investigate quality issues and consider listing review',
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Get aggregate anomaly summary.
     */
    public function getAnomalySummary(int $companyId): array
    {
        $orderAnomalies      = $this->scanOrderAnomalies($companyId);
        $inventoryAnomalies  = $this->scanInventoryAnomalies($companyId);
        $settlementAnomalies = $this->scanSettlementAnomalies($companyId);
        $returnAnomalies     = $this->scanReturnAnomalies($companyId);

        $all = array_merge($orderAnomalies, $inventoryAnomalies, $settlementAnomalies, $returnAnomalies);

        // Sort by severity
        $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($all, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 4) <=> ($severityOrder[$b['severity']] ?? 4));

        return [
            'anomalies'  => $all,
            'total'      => count($all),
            'critical'   => count(array_filter($all, fn ($a) => $a['severity'] === 'critical')),
            'high'       => count(array_filter($all, fn ($a) => $a['severity'] === 'high')),
            'medium'     => count(array_filter($all, fn ($a) => $a['severity'] === 'medium')),
            'low'        => count(array_filter($all, fn ($a) => $a['severity'] === 'low')),
            'by_type'    => [
                'order'      => count($orderAnomalies),
                'inventory'  => count($inventoryAnomalies),
                'settlement' => count($settlementAnomalies),
                'return'     => count($returnAnomalies),
            ],
        ];
    }
}
