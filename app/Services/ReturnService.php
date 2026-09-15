<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\ReturnInspection;
use App\Models\ReturnItem;
use App\Models\Sku;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * Initiate a return for an order.
     */
    public function initiateReturn(Order $order, array $data): ReturnOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $return = ReturnOrder::create([
                'company_id'          => $order->company_id,
                'order_id'            => $order->id,
                'sub_order_id'        => $data['sub_order_id'] ?? null,
                'return_type'         => $data['return_type'],
                'reason_category'     => $data['reason_category'],
                'reason_detail'       => $data['reason_detail'] ?? null,
                'status'              => ReturnOrder::STATUS_INITIATED,
                'marketplace_return_id' => $data['marketplace_return_id'] ?? null,
                'tracking_number'     => $data['tracking_number'] ?? null,
                'courier_name'        => $data['courier_name'] ?? null,
                'initiated_at'        => now(),
                'return_address_json' => $data['return_address_json'] ?? null,
            ]);

            // Create return items from provided data
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    ReturnItem::create([
                        'return_id'  => $return->id,
                        'sku_id'     => $item['sku_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity'   => $item['quantity'] ?? 1,
                        'condition'  => $item['condition'] ?? 'like_new',
                    ]);
                }
            }

            // Update order status to return/rto if applicable
            if ($data['return_type'] === ReturnOrder::TYPE_RTO && $order->canTransitionTo(Order::STATUS_RTO)) {
                $order->changeStatus(Order::STATUS_RTO, 'RTO initiated', auth()->id());
            } elseif ($order->canTransitionTo(Order::STATUS_RETURN)) {
                $order->changeStatus(Order::STATUS_RETURN, 'Return initiated', auth()->id());
            }

            return $return;
        });
    }

    /**
     * Mark a return as received.
     */
    public function receiveReturn(ReturnOrder $return): ReturnOrder
    {
        return DB::transaction(function () use ($return) {
            $return->changeStatus(ReturnOrder::STATUS_RECEIVED);
            $return->received_at = now();
            $return->save();

            return $return;
        });
    }

    /**
     * Record an inspection for a return.
     */
    public function performInspection(ReturnOrder $return, array $inspectionData): ReturnInspection
    {
        return DB::transaction(function () use ($return, $inspectionData) {
            // Transition to inspecting if currently received
            if ($return->status === ReturnOrder::STATUS_RECEIVED) {
                $return->changeStatus(ReturnOrder::STATUS_INSPECTING);
            }

            $inspection = ReturnInspection::create([
                'return_id'        => $return->id,
                'inspected_by'     => auth()->id(),
                'condition'        => $inspectionData['condition'],
                'is_resellable'    => $inspectionData['is_resellable'] ?? false,
                'inspection_notes' => $inspectionData['inspection_notes'] ?? null,
                'photos_json'      => $inspectionData['photos_json'] ?? null,
                'inspected_at'     => now(),
            ]);

            // Update item conditions if provided
            if (! empty($inspectionData['items'])) {
                foreach ($inspectionData['items'] as $itemData) {
                    $item = ReturnItem::find($itemData['id']);
                    if ($item && $item->return_id === $return->id) {
                        $item->update([
                            'condition'        => $itemData['condition'],
                            'restock_quantity'  => $itemData['restock_quantity'] ?? 0,
                            'dispose_quantity'  => $itemData['dispose_quantity'] ?? 0,
                        ]);
                    }
                }
            }

            // Transition to inspection complete
            $return->changeStatus(ReturnOrder::STATUS_INSPECTION_COMPLETE);
            $return->inspected_at = now();
            $return->save();

            return $inspection;
        });
    }

    /**
     * Restock inspected items back to inventory.
     */
    public function restockItems(ReturnOrder $return): ReturnOrder
    {
        return DB::transaction(function () use ($return) {
            $return->load('items.sku');

            foreach ($return->items as $item) {
                if ($item->restock_quantity > 0 && $item->sku) {
                    // Find default warehouse for the company
                    $warehouse = Warehouse::where('company_id', $return->company_id)->first();

                    if ($warehouse) {
                        $type = $return->return_type === ReturnOrder::TYPE_RTO
                            ? InventoryService::TYPE_RTO_RECEIPT
                            : InventoryService::TYPE_RETURN_RECEIPT;

                        $this->inventoryService->addStock(
                            $item->sku,
                            $warehouse,
                            $item->restock_quantity,
                            $item->sku->cost_price ?? 0,
                            $type,
                            'RETURN-' . $return->id,
                            auth()->id(),
                        );
                    }
                }
            }

            $return->changeStatus(ReturnOrder::STATUS_RESTOCKED);

            return $return;
        });
    }

    /**
     * Dispose of items that cannot be restocked.
     */
    public function disposeItems(ReturnOrder $return, array $itemIds): ReturnOrder
    {
        return DB::transaction(function () use ($return, $itemIds) {
            $return->load('items.sku');

            foreach ($return->items as $item) {
                if (in_array($item->id, $itemIds) && $item->sku) {
                    $warehouse = Warehouse::where('company_id', $return->company_id)->first();

                    if ($warehouse && $item->dispose_quantity > 0) {
                        $this->inventoryService->adjustStock(
                            $item->sku,
                            $warehouse,
                            $item->dispose_quantity,
                            'Return disposal - RETURN-' . $return->id,
                            auth()->id(),
                            'damage',
                        );
                    }
                }
            }

            $return->changeStatus(ReturnOrder::STATUS_DISPOSED);

            return $return;
        });
    }

    /**
     * Close a return.
     */
    public function closeReturn(ReturnOrder $return): ReturnOrder
    {
        return DB::transaction(function () use ($return) {
            $return->changeStatus(ReturnOrder::STATUS_CLOSED);
            $return->closed_at = now();
            $return->save();

            return $return;
        });
    }
}
