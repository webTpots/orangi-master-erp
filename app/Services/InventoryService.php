<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLedger;
use App\Models\Sku;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    // ── Transaction Types (from BR-INV-006) ─────────────────────

    const TYPE_PURCHASE_RECEIPT      = 'purchase_receipt';
    const TYPE_SALE_RESERVE          = 'sale_reserve';
    const TYPE_SALE_DISPATCH         = 'sale_dispatch';
    const TYPE_SALE_CANCEL_UNRESERVE = 'sale_cancel_unreserve';
    const TYPE_RETURN_RECEIPT        = 'return_receipt';
    const TYPE_RTO_RECEIPT           = 'rto_receipt';
    const TYPE_DAMAGE_OUT            = 'damage_out';
    const TYPE_ADJUSTMENT_IN         = 'adjustment_in';
    const TYPE_ADJUSTMENT_OUT        = 'adjustment_out';
    const TYPE_TRANSFER_OUT          = 'transfer_out';
    const TYPE_TRANSFER_IN           = 'transfer_in';
    const TYPE_OPENING_BALANCE       = 'opening_balance';
    const TYPE_BLOCK                 = 'block';
    const TYPE_UNBLOCK               = 'unblock';

    // ── Core Methods ────────────────────────────────────────────

    /**
     * Add stock (IN direction).
     */
    public function addStock(
        Sku $sku,
        Warehouse $warehouse,
        int $qty,
        float $unitCost,
        string $type,
        ?string $reference = null,
        ?int $userId = null
    ): InventoryLedger {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $warehouse, $qty, $unitCost, $type, $reference, $userId) {
            $item = $this->findOrCreateItem($sku, $warehouse);

            $stockBefore = $item->physical_stock;

            // Weighted average costing (BR-CST-001)
            if ($item->physical_stock > 0 && $item->unit_cost > 0) {
                $item->unit_cost = (($item->physical_stock * $item->unit_cost) + ($qty * $unitCost))
                    / ($item->physical_stock + $qty);
            } else {
                $item->unit_cost = $unitCost;
            }

            $item->physical_stock += $qty;
            $item->recalculateAvailable();
            $item->total_value = $item->physical_stock * $item->unit_cost;
            $item->save();

            return $this->createLedgerEntry($item, $type, $qty, $stockBefore, $item->physical_stock, $unitCost, $reference, $userId);
        });
    }

    /**
     * Deduct stock (OUT direction).
     */
    public function deductStock(
        Sku $sku,
        Warehouse $warehouse,
        int $qty,
        string $type,
        ?string $reference = null,
        ?int $userId = null
    ): InventoryLedger {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $warehouse, $qty, $type, $reference, $userId) {
            $item = $this->findOrCreateItem($sku, $warehouse);

            // BR-INV-004: Negative stock prevention
            if ($item->available_stock < $qty && $type !== self::TYPE_ADJUSTMENT_OUT) {
                throw new \RuntimeException(
                    "Insufficient available stock for {$sku->sku_code}. Available: {$item->available_stock}, Requested: {$qty}"
                );
            }

            $stockBefore = $item->physical_stock;

            $item->physical_stock -= $qty;
            $item->recalculateAvailable();
            $item->total_value = $item->physical_stock * ($item->unit_cost ?? 0);
            $item->save();

            return $this->createLedgerEntry($item, $type, -$qty, $stockBefore, $item->physical_stock, $item->unit_cost, $reference, $userId);
        });
    }

    /**
     * Reserve stock for an order (BR-INV-005).
     */
    public function reserveStock(
        Sku $sku,
        Warehouse $warehouse,
        int $qty,
        string $reference
    ): InventoryLedger {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $warehouse, $qty, $reference) {
            $item = $this->findOrCreateItem($sku, $warehouse);

            if ($item->available_stock < $qty) {
                throw new \RuntimeException(
                    "Insufficient available stock for reservation. Available: {$item->available_stock}, Requested: {$qty}"
                );
            }

            $stockBefore = $item->physical_stock;

            $item->reserved_stock += $qty;
            $item->recalculateAvailable();
            $item->save();

            return $this->createLedgerEntry($item, self::TYPE_SALE_RESERVE, -$qty, $stockBefore, $item->physical_stock, $item->unit_cost, $reference);
        });
    }

    /**
     * Release a reservation (order cancelled).
     */
    public function releaseReservation(
        Sku $sku,
        Warehouse $warehouse,
        int $qty,
        string $reference
    ): InventoryLedger {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $warehouse, $qty, $reference) {
            $item = $this->findOrCreateItem($sku, $warehouse);

            $item->reserved_stock = max(0, $item->reserved_stock - $qty);
            $item->recalculateAvailable();
            $item->save();

            return $this->createLedgerEntry($item, self::TYPE_SALE_CANCEL_UNRESERVE, $qty, $item->physical_stock, $item->physical_stock, $item->unit_cost, $reference);
        });
    }

    /**
     * Manual stock adjustment (physical count difference, damage, block/unblock).
     */
    public function adjustStock(
        Sku $sku,
        Warehouse $warehouse,
        int $qty,
        string $reason,
        ?int $userId = null,
        string $adjustmentType = 'add'
    ): InventoryLedger {
        return DB::transaction(function () use ($sku, $warehouse, $qty, $reason, $userId, $adjustmentType) {
            $item = $this->findOrCreateItem($sku, $warehouse);
            $stockBefore = $item->physical_stock;
            $absQty = abs($qty);

            switch ($adjustmentType) {
                case 'add':
                    $item->physical_stock += $absQty;
                    $type = self::TYPE_ADJUSTMENT_IN;
                    $ledgerQty = $absQty;
                    break;

                case 'remove':
                    $item->physical_stock = max(0, $item->physical_stock - $absQty);
                    $type = self::TYPE_ADJUSTMENT_OUT;
                    $ledgerQty = -$absQty;
                    break;

                case 'damage':
                    $item->physical_stock = max(0, $item->physical_stock - $absQty);
                    $item->damaged_stock += $absQty;
                    $type = self::TYPE_DAMAGE_OUT;
                    $ledgerQty = -$absQty;
                    break;

                case 'block':
                    $item->blocked_stock += $absQty;
                    $type = self::TYPE_BLOCK;
                    $ledgerQty = -$absQty;
                    break;

                case 'unblock':
                    $item->blocked_stock = max(0, $item->blocked_stock - $absQty);
                    $type = self::TYPE_UNBLOCK;
                    $ledgerQty = $absQty;
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid adjustment type: {$adjustmentType}");
            }

            $item->recalculateAvailable();
            $item->total_value = $item->physical_stock * ($item->unit_cost ?? 0);
            $item->save();

            return $this->createLedgerEntry($item, $type, $ledgerQty, $stockBefore, $item->physical_stock, $item->unit_cost, null, $userId, $reason);
        });
    }

    /**
     * Transfer stock between warehouses.
     *
     * @return array{out: InventoryLedger, in: InventoryLedger}
     */
    public function transferStock(
        Sku $sku,
        Warehouse $from,
        Warehouse $to,
        int $qty,
        ?int $userId = null
    ): array {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($sku, $from, $to, $qty, $userId) {
            $fromItem = $this->findOrCreateItem($sku, $from);

            if ($fromItem->available_stock < $qty) {
                throw new \RuntimeException(
                    "Insufficient available stock for transfer. Available: {$fromItem->available_stock}, Requested: {$qty}"
                );
            }

            $reference = 'TRANSFER-' . now()->format('YmdHis');

            // Deduct from source
            $fromBefore = $fromItem->physical_stock;
            $fromItem->physical_stock -= $qty;
            $fromItem->recalculateAvailable();
            $fromItem->total_value = $fromItem->physical_stock * ($fromItem->unit_cost ?? 0);
            $fromItem->save();

            $outEntry = $this->createLedgerEntry($fromItem, self::TYPE_TRANSFER_OUT, -$qty, $fromBefore, $fromItem->physical_stock, $fromItem->unit_cost, $reference, $userId);

            // Add to destination
            $toItem = $this->findOrCreateItem($sku, $to);
            $toBefore = $toItem->physical_stock;
            $transferCost = $fromItem->unit_cost ?? $sku->cost_price ?? 0;

            // Weighted average costing for destination
            if ($toItem->physical_stock > 0 && $toItem->unit_cost > 0) {
                $toItem->unit_cost = (($toItem->physical_stock * $toItem->unit_cost) + ($qty * $transferCost))
                    / ($toItem->physical_stock + $qty);
            } else {
                $toItem->unit_cost = $transferCost;
            }

            $toItem->physical_stock += $qty;
            $toItem->recalculateAvailable();
            $toItem->total_value = $toItem->physical_stock * $toItem->unit_cost;
            $toItem->save();

            $inEntry = $this->createLedgerEntry($toItem, self::TYPE_TRANSFER_IN, $qty, $toBefore, $toItem->physical_stock, $transferCost, $reference, $userId);

            return ['out' => $outEntry, 'in' => $inEntry];
        });
    }

    /**
     * Import opening stock in bulk.
     *
     * @param  array  $items  [{sku_id, quantity, unit_cost}]
     * @return ImportBatch
     */
    public function importOpeningStock(array $items, int $warehouseId, int $companyId, int $userId): ImportBatch
    {
        return DB::transaction(function () use ($items, $warehouseId, $companyId, $userId) {
            $warehouse = Warehouse::findOrFail($warehouseId);

            $batch = ImportBatch::create([
                'company_id'       => $companyId,
                'uploaded_by'      => $userId,
                'type'             => 'opening_stock',
                'total_records'    => count($items),
                'processed_records' => 0,
                'successful_records' => 0,
                'failed_records'   => 0,
                'status'           => 'processing',
                'started_at'       => now(),
            ]);

            $successful = 0;
            $failed = 0;

            foreach ($items as $entry) {
                try {
                    $sku = Sku::findOrFail($entry['sku_id']);
                    $qty = (int) $entry['quantity'];
                    $cost = (float) ($entry['unit_cost'] ?? $sku->cost_price ?? 0);

                    if ($qty > 0) {
                        $this->addStock($sku, $warehouse, $qty, $cost, self::TYPE_OPENING_BALANCE, 'BATCH-' . $batch->id, $userId);
                        $successful++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                }
            }

            $batch->update([
                'processed_records'  => $successful + $failed,
                'successful_records' => $successful,
                'failed_records'     => $failed,
                'status'             => $failed === 0 ? 'completed' : 'completed_with_errors',
                'completed_at'       => now(),
            ]);

            return $batch;
        });
    }

    /**
     * Get stock balance for a SKU, optionally per warehouse.
     *
     * @return array{physical: int, reserved: int, available: int, damaged: int, blocked: int}
     */
    public function getStockBalance(Sku $sku, ?Warehouse $warehouse = null): array
    {
        $query = InventoryItem::where('sku_id', $sku->id);

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        $items = $query->get();

        return [
            'physical'  => $items->sum('physical_stock'),
            'reserved'  => $items->sum('reserved_stock'),
            'available' => $items->sum('available_stock'),
            'damaged'   => $items->sum('damaged_stock'),
            'blocked'   => $items->sum('blocked_stock'),
        ];
    }

    /**
     * Recalculate balance from ledger entries (reconciliation).
     */
    public function recalculateBalance(InventoryItem $item): void
    {
        $ledgerSum = InventoryLedger::where('inventory_item_id', $item->id)->sum('quantity');

        $item->physical_stock = max(0, $ledgerSum);
        $item->recalculateAvailable();
        $item->total_value = $item->physical_stock * ($item->unit_cost ?? 0);
        $item->save();
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function findOrCreateItem(Sku $sku, Warehouse $warehouse): InventoryItem
    {
        return InventoryItem::firstOrCreate(
            ['sku_id' => $sku->id, 'warehouse_id' => $warehouse->id],
            [
                'company_id'     => $sku->company_id,
                'physical_stock' => 0,
                'reserved_stock' => 0,
                'available_stock' => 0,
                'damaged_stock'  => 0,
                'blocked_stock'  => 0,
                'in_transit_stock' => 0,
            ]
        );
    }

    private function createLedgerEntry(
        InventoryItem $item,
        string $type,
        int $quantity,
        int $balanceBefore,
        int $balanceAfter,
        ?float $unitCost,
        ?string $reference = null,
        ?int $userId = null,
        ?string $reason = null
    ): InventoryLedger {
        return InventoryLedger::create([
            'inventory_item_id' => $item->id,
            'company_id'        => $item->company_id,
            'sku_id'            => $item->sku_id,
            'warehouse_id'      => $item->warehouse_id,
            'transaction_type'  => $type,
            'quantity'          => $quantity,
            'balance_before'    => $balanceBefore,
            'balance_after'     => $balanceAfter,
            'unit_cost'         => $unitCost,
            'total_cost'        => abs($quantity) * ($unitCost ?? 0),
            'source_document'   => $reference,
            'reason'            => $reason,
            'performed_by'      => $userId,
        ]);
    }
}
