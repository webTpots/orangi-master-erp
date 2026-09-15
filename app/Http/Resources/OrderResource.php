<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'marketplace_order_id' => $this->marketplace_order_id,
            'order_date'           => $this->order_date?->toIso8601String(),
            'status'               => $this->status,
            'status_label'         => $this->status_label,
            'customer_name'        => $this->customer_name,
            'customer_city'        => $this->customer_city,
            'customer_state'       => $this->customer_state,
            'customer_pincode'     => $this->customer_pincode,
            'payment_type'         => $this->payment_type,
            'total_amount'         => (float) $this->total_amount,
            'invoice_number'       => $this->invoice_number,
            'invoice_amount'       => (float) $this->invoice_amount,
            'courier_partner'      => $this->courier_partner,
            'awb_number'           => $this->awb_number,
            'tracking_url'         => $this->tracking_url,
            'is_sla_risk'          => $this->is_sla_risk,
            'sla_date'             => $this->sla_date?->toIso8601String(),
            'marketplace'          => $this->whenLoaded('marketplace', fn () => [
                'id'   => $this->marketplace->id,
                'name' => $this->marketplace->name,
            ]),
            'sub_orders' => $this->whenLoaded('subOrders', fn () => $this->subOrders->map(fn ($so) => [
                'id'                => $so->id,
                'sub_order_number'  => $so->sub_order_number,
                'marketplace_sku'   => $so->marketplace_sku,
                'product_name'      => $so->product_name,
                'color'             => $so->color,
                'size'              => $so->size,
                'quantity'          => $so->quantity,
                'unit_price'        => (float) $so->unit_price,
                'line_total'        => (float) $so->line_total,
                'stock_status'      => $so->stock_status,
                'processing_status' => $so->processing_status,
            ])),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($l) => [
                'id'              => $l->id,
                'tracking_number' => $l->tracking_number ?? $l->awb_number,
            ])),
            'shipments' => $this->whenLoaded('shipments', fn () => $this->shipments->map(fn ($s) => [
                'id'              => $s->id,
                'tracking_number' => $s->tracking_number,
                'status'          => $s->status,
                'courier_name'    => $s->courier_name,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
