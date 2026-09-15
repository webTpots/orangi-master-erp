<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryItem;
use App\Models\InventoryLedger;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Create a new Purchase Order with lines.
     *
     * @param  int    $vendorId
     * @param  array  $lines   [{sku_id, quantity, unit_cost}]
     * @param  int    $companyId
     * @param  int    $userId
     * @param  array  $extra   Optional: order_date, expected_delivery_date, notes
     * @return PurchaseOrder
     */
    public function createPO(int $vendorId, array $lines, int $companyId, int $userId, array $extra = []): PurchaseOrder
    {
        return DB::transaction(function () use ($vendorId, $lines, $companyId, $userId, $extra) {
            $po = PurchaseOrder::create([
                'company_id'             => $companyId,
                'vendor_id'              => $vendorId,
                'order_date'             => $extra['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $extra['expected_delivery_date'] ?? null,
                'status'                 => 'draft',
                'total_amount'           => 0,
                'notes'                  => $extra['notes'] ?? null,
                'created_by'             => $userId,
            ]);

            $totalAmount = 0;

            foreach ($lines as $line) {
                $lineTotal = $line['quantity'] * $line['unit_cost'];
                $totalAmount += $lineTotal;

                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'sku_id'            => $line['sku_id'],
                    'quantity_requested' => $line['quantity'],
                    'quantity_confirmed' => 0,
                    'quantity_received'  => 0,
                    'unit_cost'          => $line['unit_cost'],
                    'line_total'         => $lineTotal,
                    'status'             => 'pending',
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            return $po->fresh(['lines', 'vendor']);
        });
    }

    /**
     * Approve a PO (move from draft to sent).
     */
    public function approvePO(PurchaseOrder $po, int $userId): void
    {
        if (! $po->canBeApproved()) {
            throw new \RuntimeException('This PO cannot be approved in its current state.');
        }

        $po->update([
            'status'      => 'sent',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Confirm a line's quantity from vendor.
     */
    public function confirmLine(PurchaseOrderLine $line, int $confirmedQty): void
    {
        $line->update([
            'quantity_confirmed' => $confirmedQty,
            'status'             => $confirmedQty >= $line->quantity_requested ? 'confirmed' : 'partial',
        ]);

        // Update PO status based on all lines
        $po = $line->purchaseOrder;
        $allConfirmed = $po->lines()->where('status', '!=', 'confirmed')->doesntExist();
        $anyConfirmed = $po->lines()->whereIn('status', ['confirmed', 'partial'])->exists();

        if ($allConfirmed) {
            $po->update(['status' => 'confirmed']);
        } elseif ($anyConfirmed) {
            $po->update(['status' => 'partially_confirmed']);
        }
    }

    /**
     * Receive goods against a PO.
     *
     * @param  PurchaseOrder $po
     * @param  array         $receivedLines [{po_line_id, quantity_received, quantity_accepted, quantity_rejected, rejection_reason?, unit_cost?}]
     * @param  int           $warehouseId
     * @param  int           $userId
     * @return GoodsReceipt
     */
    public function receiveGoods(PurchaseOrder $po, array $receivedLines, int $warehouseId, int $userId): GoodsReceipt
    {
        if (! $po->canReceiveGoods()) {
            throw new \RuntimeException('This PO cannot receive goods in its current state.');
        }

        return DB::transaction(function () use ($po, $receivedLines, $warehouseId, $userId) {
            $gr = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'company_id'        => $po->company_id,
                'warehouse_id'      => $warehouseId,
                'receipt_date'       => now()->toDateString(),
                'received_by'       => $userId,
            ]);

            foreach ($receivedLines as $rl) {
                $poLine = PurchaseOrderLine::findOrFail($rl['po_line_id']);
                $qtyReceived = (int) $rl['quantity_received'];
                $qtyAccepted = (int) ($rl['quantity_accepted'] ?? $qtyReceived);
                $qtyRejected = (int) ($rl['quantity_rejected'] ?? 0);
                $unitCost = $rl['unit_cost'] ?? $poLine->unit_cost;

                // Create goods receipt line
                GoodsReceiptLine::create([
                    'goods_receipt_id'  => $gr->id,
                    'po_line_id'        => $poLine->id,
                    'sku_id'            => $poLine->sku_id,
                    'quantity_received'  => $qtyReceived,
                    'quantity_accepted'  => $qtyAccepted,
                    'quantity_rejected'  => $qtyRejected,
                    'rejection_reason'   => $rl['rejection_reason'] ?? null,
                    'unit_cost'          => $unitCost,
                ]);

                // Update PO line received qty
                $newTotalReceived = $poLine->quantity_received + $qtyAccepted;
                $poLine->update([
                    'quantity_received' => $newTotalReceived,
                    'status'            => $newTotalReceived >= $poLine->quantity_requested ? 'received' : 'partial',
                ]);

                // Update inventory
                if ($qtyAccepted > 0) {
                    $this->addToInventory($poLine->sku_id, $warehouseId, $po->company_id, $qtyAccepted, $unitCost, $gr, $userId);
                }
            }

            // Update PO status
            $allReceived = $po->lines()->where('status', '!=', 'received')->doesntExist();
            $anyReceived = $po->lines()->whereIn('status', ['received', 'partial'])->where('quantity_received', '>', 0)->exists();

            if ($allReceived) {
                $po->update(['status' => 'received']);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partially_received']);
            }

            return $gr->fresh(['lines']);
        });
    }

    /**
     * Cancel a PO.
     */
    public function cancelPO(PurchaseOrder $po, string $reason): void
    {
        if (! $po->canBeCancelled()) {
            throw new \RuntimeException('This PO cannot be cancelled in its current state.');
        }

        DB::transaction(function () use ($po, $reason) {
            $po->update([
                'status'           => 'cancelled',
                'cancelled_at'     => now(),
                'cancelled_reason' => $reason,
            ]);

            $po->lines()->update(['status' => 'cancelled']);
        });
    }

    /**
     * Calculate shortage for a PO.
     *
     * @return array [{sku_id, sku_code, requested, confirmed, shortage}]
     */
    public function calculateShortage(PurchaseOrder $po): array
    {
        $shortages = [];

        foreach ($po->lines()->with('sku')->get() as $line) {
            $shortage = $line->quantity_requested - $line->quantity_confirmed;
            $shortages[] = [
                'sku_id'    => $line->sku_id,
                'sku_code'  => $line->sku->sku_code,
                'requested' => $line->quantity_requested,
                'confirmed' => $line->quantity_confirmed,
                'shortage'  => max(0, $shortage),
            ];
        }

        return $shortages;
    }

    // ── Private Helpers ──────────────────────────────────────────

    private function addToInventory(int $skuId, int $warehouseId, int $companyId, int $quantity, float $unitCost, GoodsReceipt $gr, int $userId): void
    {
        // Find or create inventory item
        $item = InventoryItem::firstOrCreate(
            ['sku_id' => $skuId, 'warehouse_id' => $warehouseId],
            ['company_id' => $companyId, 'physical_stock' => 0, 'reserved_stock' => 0, 'available_stock' => 0]
        );

        $stockBefore = $item->physical_stock;
        $item->physical_stock += $quantity;
        $item->available_stock += $quantity;
        $item->unit_cost = $unitCost;
        $item->total_value = $item->physical_stock * $unitCost;
        $item->save();

        // Create ledger entry
        InventoryLedger::create([
            'inventory_item_id' => $item->id,
            'company_id'        => $companyId,
            'sku_id'            => $skuId,
            'warehouse_id'      => $warehouseId,
            'transaction_type'  => 'purchase_receipt',
            'quantity'          => $quantity,
            'unit_cost'         => $unitCost,
            'total_cost'        => $quantity * $unitCost,
            'balance_before'    => $stockBefore,
            'balance_after'     => $item->physical_stock,
            'reference_type'    => GoodsReceipt::class,
            'reference_id'      => $gr->id,
            'source_document'   => $gr->receipt_number,
            'performed_by'      => $userId,
            'notes'             => "Goods receipt {$gr->receipt_number} from PO {$gr->purchaseOrder->po_number}",
        ]);
    }
}
