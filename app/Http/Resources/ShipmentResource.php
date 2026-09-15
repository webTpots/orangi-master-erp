<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'order_id'              => $this->order_id,
            'tracking_number'       => $this->tracking_number,
            'awb_number'            => $this->awb_number,
            'courier_name'          => $this->courier_name,
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'shipped_at'            => $this->shipped_at?->toIso8601String(),
            'delivered_at'          => $this->delivered_at?->toIso8601String(),
            'estimated_delivery_at' => $this->estimated_delivery_at?->toIso8601String(),
            'last_scan_at'          => $this->last_scan_at?->toIso8601String(),
            'last_scan_location'    => $this->last_scan_location,
            'last_scan_status'      => $this->last_scan_status,
            'weight_grams'          => $this->weight_grams,
            'delivery_address'      => $this->delivery_address_json,
            'notes'                 => $this->notes,
            'order' => $this->whenLoaded('order', fn () => [
                'id'                   => $this->order->id,
                'marketplace_order_id' => $this->order->marketplace_order_id,
                'customer_name'        => $this->order->customer_name,
                'status'               => $this->order->status,
            ]),
            'scans' => $this->whenLoaded('scans', fn () => $this->scans->map(fn ($scan) => [
                'id'                 => $scan->id,
                'scan_type'          => $scan->scan_type,
                'scanned_at'         => $scan->scanned_at?->toIso8601String(),
                'location'           => $scan->location,
                'city'               => $scan->city,
                'status_code'        => $scan->status_code,
                'status_description' => $scan->status_description,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
