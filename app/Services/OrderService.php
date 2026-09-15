<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private SkuIntelligence $skuIntelligence,
    ) {}

    /**
     * Create an order (and sub-order) from parsed label data.
     *
     * If an order with the same marketplace_order_id already exists for
     * this company + marketplace, the existing order is returned and a
     * new sub-order is appended when the sub_order_number is unique.
     */
    public function createOrderFromLabel(array $labelData, int $companyId, int $marketplaceId): Order
    {
        return DB::transaction(function () use ($labelData, $companyId, $marketplaceId) {
            $marketplaceOrderId = $labelData['marketplace_order_id']
                ?? preg_replace('/_\d+$/', '', $labelData['sub_order_number'] ?? '');

            // Find or create the parent order
            $order = Order::firstOrCreate(
                [
                    'company_id'           => $companyId,
                    'marketplace_id'       => $marketplaceId,
                    'marketplace_order_id' => $marketplaceOrderId,
                ],
                [
                    'order_date'       => $labelData['order_date'] ?? now()->toDateString(),
                    'status'           => Order::STATUS_NEW,
                    'customer_name'    => $labelData['customer_name'] ?? null,
                    'customer_address' => $labelData['customer_address'] ?? null,
                    'customer_city'    => $labelData['customer_city'] ?? null,
                    'customer_state'   => $labelData['customer_state'] ?? null,
                    'customer_pincode' => $labelData['customer_pincode'] ?? null,
                    'payment_type'     => $labelData['payment_type'] ?? null,
                    'courier_partner'  => $labelData['courier_partner'] ?? null,
                    'awb_number'       => $labelData['awb_number'] ?? null,
                    'invoice_number'   => $labelData['invoice_number'] ?? null,
                    'invoice_date'     => $labelData['invoice_date'] ?? null,
                    'invoice_amount'   => $labelData['invoice_amount'] ?? null,
                    'hsn_code'         => $labelData['hsn_code'] ?? null,
                    'taxable_value'    => $labelData['taxable_value'] ?? null,
                    'igst'             => $labelData['igst'] ?? null,
                    'sgst'             => $labelData['sgst'] ?? null,
                    'cgst'             => $labelData['cgst'] ?? null,
                    'other_charges'    => $labelData['other_charges'] ?? null,
                    'total_amount'     => $labelData['invoice_amount'] ?? 0,
                ]
            );

            // Update customer / shipping details if they were missing
            if (! $order->customer_name && ($labelData['customer_name'] ?? null)) {
                $order->update([
                    'customer_name'    => $labelData['customer_name'],
                    'customer_address' => $labelData['customer_address'] ?? $order->customer_address,
                    'customer_city'    => $labelData['customer_city'] ?? $order->customer_city,
                    'customer_state'   => $labelData['customer_state'] ?? $order->customer_state,
                    'customer_pincode' => $labelData['customer_pincode'] ?? $order->customer_pincode,
                ]);
            }

            // Create sub-order if sub_order_number present and not duplicate
            $subOrderNumber = $labelData['sub_order_number'] ?? null;
            if ($subOrderNumber) {
                $subOrder = SubOrder::firstOrCreate(
                    [
                        'company_id'       => $companyId,
                        'sub_order_number' => $subOrderNumber,
                    ],
                    [
                        'order_id'          => $order->id,
                        'marketplace_sku'   => $labelData['sku'] ?? null,
                        'product_name'      => $labelData['product_name'] ?? null,
                        'color'             => $labelData['color'] ?? null,
                        'size'              => $labelData['size'] ?? null,
                        'quantity'          => $labelData['quantity'] ?? 1,
                        'unit_price'        => $labelData['invoice_amount'] ?? null,
                        'line_total'        => $labelData['invoice_amount'] ?? null,
                        'stock_status'      => 'pending',
                        'processing_status' => 'pending',
                    ]
                );

                // Attempt SKU mapping
                $stockStatus = $this->checkStockForSubOrder($subOrder);
                if ($subOrder->stock_status !== $stockStatus) {
                    $subOrder->update(['stock_status' => $stockStatus]);
                }
            }

            return $order->fresh(['subOrders']);
        });
    }

    /**
     * Change an order's status with validation and audit trail.
     *
     * @throws \RuntimeException
     */
    public function changeOrderStatus(Order $order, string $newStatus, ?string $notes = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $newStatus, $notes, $userId) {
            // Lock the row to prevent concurrent transitions
            $order = Order::lockForUpdate()->find($order->id);
            $order->changeStatus($newStatus, $notes, $userId);
        });
    }

    /**
     * Check stock availability for a sub-order using SkuIntelligence.
     *
     * @return string One of: pending, in_stock, partial, out_of_stock, not_mapped
     */
    public function checkStockForSubOrder(SubOrder $subOrder): string
    {
        $externalSku = $subOrder->marketplace_sku;
        if (! $externalSku) {
            return 'not_mapped';
        }

        $matches = $this->skuIntelligence->findMatch(
            $externalSku,
            $subOrder->product_name,
            $subOrder->color,
            $subOrder->size,
            $subOrder->company_id,
        );

        if (empty($matches)) {
            return 'not_mapped';
        }

        $bestMatch = $matches[0];
        $sku = $bestMatch['sku'];

        // If confidence is too low, mark as not_mapped
        if ($bestMatch['confidence'] < 0.60) {
            return 'not_mapped';
        }

        // Link the SKU if confident enough
        if ($bestMatch['confidence'] >= 0.80 && ! $subOrder->sku_id) {
            $subOrder->update(['sku_id' => $sku->id]);
        }

        // Check inventory for this SKU
        $inventory = InventoryItem::where('sku_id', $sku->id)
            ->where('company_id', $subOrder->company_id)
            ->first();

        if (! $inventory) {
            return 'out_of_stock';
        }

        if ($inventory->available_stock >= $subOrder->quantity) {
            return 'in_stock';
        }

        if ($inventory->available_stock > 0) {
            return 'partial';
        }

        return 'out_of_stock';
    }

    /**
     * Process an order: mark sub-orders as picked, deduct reserved stock.
     */
    public function processOrder(Order $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            foreach ($order->subOrders as $subOrder) {
                if ($subOrder->sku_id && $subOrder->processing_status === 'pending') {
                    $inventory = InventoryItem::where('sku_id', $subOrder->sku_id)
                        ->where('company_id', $subOrder->company_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory && $inventory->available_stock >= $subOrder->quantity) {
                        $inventory->reserved_stock += $subOrder->quantity;
                        $inventory->recalculateAvailable();
                        $inventory->save();

                        $subOrder->update(['processing_status' => 'picked']);
                    }
                }
            }

            if ($order->status === Order::STATUS_NEW) {
                $this->changeOrderStatus($order, Order::STATUS_ACCEPTED, 'Order processed', $userId);
            }
        });
    }
}
