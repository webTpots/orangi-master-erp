<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'order_id'              => $this->order_id,
            'return_type'           => $this->return_type,
            'type_label'            => $this->type_label,
            'reason_category'       => $this->reason_category,
            'reason_label'          => $this->reason_label,
            'reason_detail'         => $this->reason_detail,
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'marketplace_return_id' => $this->marketplace_return_id,
            'tracking_number'       => $this->tracking_number,
            'courier_name'          => $this->courier_name,
            'initiated_at'          => $this->initiated_at?->toIso8601String(),
            'received_at'           => $this->received_at?->toIso8601String(),
            'inspected_at'          => $this->inspected_at?->toIso8601String(),
            'closed_at'             => $this->closed_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn () => [
                'id'                   => $this->order->id,
                'marketplace_order_id' => $this->order->marketplace_order_id,
                'customer_name'        => $this->order->customer_name,
                'total_amount'         => (float) $this->order->total_amount,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'               => $item->id,
                'sku_id'           => $item->sku_id,
                'quantity'         => $item->quantity,
                'condition'        => $item->condition,
                'restock_quantity'  => $item->restock_quantity ?? 0,
                'dispose_quantity'  => $item->dispose_quantity ?? 0,
            ])),
            'inspections' => $this->whenLoaded('inspections', fn () => $this->inspections->map(fn ($ins) => [
                'id'               => $ins->id,
                'condition'        => $ins->condition,
                'is_resellable'    => $ins->is_resellable,
                'inspection_notes' => $ins->inspection_notes,
                'inspected_at'     => $ins->inspected_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
