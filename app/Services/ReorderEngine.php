<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\ReorderRule;
use App\Models\ReorderSuggestion;
use App\Models\Sku;
use App\Models\VendorProduct;
use Illuminate\Support\Facades\DB;

class ReorderEngine
{
    public function __construct(
        private DemandForecastService $forecastService,
        private PurchaseService $purchaseService,
    ) {}

    /**
     * Run reorder check for all SKUs in a company.
     */
    public function runReorderCheck(int $companyId): int
    {
        $suggestions = $this->generateSuggestions($companyId);

        return count($suggestions);
    }

    /**
     * Generate reorder suggestions based on active rules.
     */
    public function generateSuggestions(int $companyId): array
    {
        $rules = ReorderRule::forCompany($companyId)
            ->active()
            ->with(['sku', 'design', 'vendor'])
            ->get();

        // Get all inventory items for the company
        $inventoryItems = InventoryItem::forCompany($companyId)
            ->with(['sku.variant.product.design'])
            ->get()
            ->keyBy('sku_id');

        // Also find SKUs with zero stock that might need reordering
        $allSkuIds = Sku::where('company_id', $companyId)
            ->active()
            ->pluck('id');

        $suggestions = [];

        foreach ($rules as $rule) {
            $targetSkuIds = $this->getTargetSkuIds($rule, $companyId, $allSkuIds);

            foreach ($targetSkuIds as $skuId) {
                $item = $inventoryItems->get($skuId);

                $result = $this->applyRule($rule, $item, $companyId, $skuId);

                if ($result) {
                    // Check if a pending suggestion already exists for this SKU
                    $existing = ReorderSuggestion::forCompany($companyId)
                        ->where('sku_id', $skuId)
                        ->pending()
                        ->first();

                    if (! $existing) {
                        $suggestion = ReorderSuggestion::create([
                            'company_id'        => $companyId,
                            'sku_id'            => $skuId,
                            'vendor_id'         => $result['vendor_id'],
                            'current_stock'     => $result['current_stock'],
                            'daily_run_rate'    => $result['daily_run_rate'],
                            'days_until_stockout' => $result['days_until_stockout'],
                            'suggested_quantity' => $result['suggested_quantity'],
                            'estimated_cost'    => $result['estimated_cost'],
                            'priority'          => $result['priority'],
                            'status'            => 'pending',
                        ]);

                        $suggestions[] = $suggestion;
                    }

                    // Mark rule as triggered
                    $rule->update(['last_triggered_at' => now()]);
                }
            }
        }

        return $suggestions;
    }

    /**
     * Evaluate a single rule against an inventory item.
     */
    public function applyRule(ReorderRule $rule, ?InventoryItem $item, int $companyId, int $skuId): ?array
    {
        $currentStock = $item?->available_stock ?? 0;

        // Calculate demand metrics
        $reorderData = $this->forecastService->calculateReorderPoint($companyId, $skuId);
        $dailyRunRate = $reorderData['avg_daily'];
        $daysUntilStockout = $reorderData['days_of_stock'];

        $needsReorder = false;
        $suggestedQty = $rule->reorder_quantity ?? $reorderData['reorder_qty'];

        switch ($rule->rule_type) {
            case 'min_stock':
                if ($currentStock <= ($rule->min_stock_threshold ?? 0)) {
                    $needsReorder = true;
                }
                break;

            case 'days_of_stock':
                if ($daysUntilStockout <= ($rule->days_of_stock_threshold ?? 7)) {
                    $needsReorder = true;
                }
                break;

            case 'forecast_based':
                // Use the forecast service's recommendation
                if ($reorderData['needs_reorder']) {
                    $needsReorder = true;
                    $suggestedQty = $reorderData['reorder_qty'];
                }
                break;

            case 'manual':
                // Manual rules are only triggered when explicitly activated
                break;
        }

        if (! $needsReorder) {
            return null;
        }

        // Cap at max order quantity if set
        if ($rule->max_order_quantity && $suggestedQty > $rule->max_order_quantity) {
            $suggestedQty = $rule->max_order_quantity;
        }

        // Find preferred vendor
        $vendorId = $rule->vendor_id;
        $estimatedCost = null;

        if (! $vendorId) {
            $preferredVP = VendorProduct::where('sku_id', $skuId)
                ->where('is_preferred', true)
                ->first();

            if (! $preferredVP) {
                $preferredVP = VendorProduct::where('sku_id', $skuId)->first();
            }

            if ($preferredVP) {
                $vendorId = $preferredVP->vendor_id;
                $estimatedCost = $preferredVP->cost_price * $suggestedQty;
            }
        } else {
            $vp = VendorProduct::where('sku_id', $skuId)
                ->where('vendor_id', $vendorId)
                ->first();

            $estimatedCost = $vp ? $vp->cost_price * $suggestedQty : null;
        }

        // Determine priority
        $priority = $this->calculatePriority($daysUntilStockout);

        return [
            'current_stock'      => $currentStock,
            'daily_run_rate'     => $dailyRunRate,
            'days_until_stockout' => $daysUntilStockout,
            'suggested_quantity' => max(1, $suggestedQty),
            'estimated_cost'     => $estimatedCost,
            'vendor_id'          => $vendorId,
            'priority'           => $priority,
        ];
    }

    /**
     * Convert a single suggestion to a PO.
     */
    public function convertToPurchaseOrder(ReorderSuggestion $suggestion): PurchaseOrder
    {
        if ($suggestion->status !== 'pending' && $suggestion->status !== 'approved') {
            throw new \RuntimeException('This suggestion cannot be converted.');
        }

        if (! $suggestion->vendor_id) {
            throw new \RuntimeException('No vendor assigned to this suggestion.');
        }

        // Get cost price from vendor product
        $vp = VendorProduct::where('sku_id', $suggestion->sku_id)
            ->where('vendor_id', $suggestion->vendor_id)
            ->first();

        $unitCost = $vp?->cost_price ?? 0;

        $po = $this->purchaseService->createPO(
            vendorId: $suggestion->vendor_id,
            lines: [[
                'sku_id'    => $suggestion->sku_id,
                'quantity'  => $suggestion->suggested_quantity,
                'unit_cost' => $unitCost,
            ]],
            companyId: $suggestion->company_id,
            userId: auth()->id(),
            extra: [
                'notes' => 'Auto-generated from reorder suggestion #' . $suggestion->id,
            ],
        );

        $suggestion->update([
            'status'            => 'converted_to_po',
            'purchase_order_id' => $po->id,
        ]);

        return $po;
    }

    /**
     * Bulk convert suggestions to POs, grouped by vendor.
     */
    public function bulkConvertToPurchaseOrder(array $suggestionIds): array
    {
        $suggestions = ReorderSuggestion::whereIn('id', $suggestionIds)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('vendor_id')
            ->get();

        // Group by vendor
        $grouped = $suggestions->groupBy('vendor_id');
        $createdPOs = [];

        foreach ($grouped as $vendorId => $vendorSuggestions) {
            $lines = [];

            foreach ($vendorSuggestions as $suggestion) {
                $vp = VendorProduct::where('sku_id', $suggestion->sku_id)
                    ->where('vendor_id', $vendorId)
                    ->first();

                $lines[] = [
                    'sku_id'    => $suggestion->sku_id,
                    'quantity'  => $suggestion->suggested_quantity,
                    'unit_cost' => $vp?->cost_price ?? 0,
                ];
            }

            $companyId = $vendorSuggestions->first()->company_id;

            $po = $this->purchaseService->createPO(
                vendorId: $vendorId,
                lines: $lines,
                companyId: $companyId,
                userId: auth()->id(),
                extra: [
                    'notes' => 'Auto-generated from reorder engine (bulk)',
                ],
            );

            // Update all suggestions
            foreach ($vendorSuggestions as $suggestion) {
                $suggestion->update([
                    'status'            => 'converted_to_po',
                    'purchase_order_id' => $po->id,
                ]);
            }

            $createdPOs[] = $po;
        }

        return $createdPOs;
    }

    /**
     * Get reorder dashboard summary.
     */
    public function getReorderDashboard(int $companyId): array
    {
        $urgentItems = ReorderSuggestion::forCompany($companyId)
            ->pending()
            ->urgent()
            ->count();

        $activeSuggestions = ReorderSuggestion::forCompany($companyId)
            ->pending()
            ->count();

        $posGeneratedToday = ReorderSuggestion::forCompany($companyId)
            ->where('status', 'converted_to_po')
            ->whereDate('updated_at', today())
            ->count();

        // Average stock coverage days across all items
        $avgCoverage = DB::table('reorder_suggestions')
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->avg('days_until_stockout');

        $urgentSuggestions = ReorderSuggestion::forCompany($companyId)
            ->pending()
            ->where('priority', 'urgent')
            ->with(['sku.variant.product.design', 'vendor'])
            ->orderBy('days_until_stockout')
            ->limit(10)
            ->get();

        $suggestionsByVendor = ReorderSuggestion::forCompany($companyId)
            ->pending()
            ->with('vendor')
            ->get()
            ->groupBy('vendor_id')
            ->map(function ($group) {
                return [
                    'vendor'      => $group->first()->vendor,
                    'count'       => $group->count(),
                    'total_cost'  => $group->sum('estimated_cost'),
                ];
            })
            ->values();

        $recentPOs = PurchaseOrder::where('company_id', $companyId)
            ->where('notes', 'like', '%reorder%')
            ->with('vendor')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'urgent_items'         => $urgentItems,
            'active_suggestions'   => $activeSuggestions,
            'pos_generated_today'  => $posGeneratedToday,
            'avg_coverage_days'    => round($avgCoverage ?? 0, 1),
            'urgent_suggestions'   => $urgentSuggestions,
            'suggestions_by_vendor' => $suggestionsByVendor,
            'recent_pos'           => $recentPOs,
        ];
    }

    // ── Private Helpers ──────────────────────────────────────────

    private function getTargetSkuIds(ReorderRule $rule, int $companyId, $allSkuIds): array
    {
        if ($rule->sku_id) {
            return [$rule->sku_id];
        }

        if ($rule->design_id) {
            return Sku::where('company_id', $companyId)
                ->active()
                ->whereHas('variant.product', function ($q) use ($rule) {
                    $q->where('design_id', $rule->design_id);
                })
                ->pluck('id')
                ->toArray();
        }

        // All SKUs
        return $allSkuIds->toArray();
    }

    private function calculatePriority(int $daysUntilStockout): string
    {
        if ($daysUntilStockout <= 2) {
            return 'urgent';
        }

        if ($daysUntilStockout <= 5) {
            return 'high';
        }

        if ($daysUntilStockout <= 10) {
            return 'medium';
        }

        return 'low';
    }
}
