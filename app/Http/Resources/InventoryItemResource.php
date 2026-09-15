<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'sku_id'          => $this->sku_id,
            'warehouse_id'    => $this->warehouse_id,
            'physical_stock'  => $this->physical_stock,
            'reserved_stock'  => $this->reserved_stock,
            'available_stock' => $this->available_stock,
            'damaged_stock'   => $this->damaged_stock,
            'blocked_stock'   => $this->blocked_stock,
            'in_transit_stock' => $this->in_transit_stock,
            'unit_cost'       => (float) $this->unit_cost,
            'total_value'     => (float) $this->total_value,
            'last_counted_at' => $this->last_counted_at?->toIso8601String(),
            'sku' => $this->whenLoaded('sku', fn () => [
                'id'                  => $this->sku->id,
                'sku_code'            => $this->sku->sku_code,
                'short_name'          => $this->sku->short_name,
                'cost_price'          => (float) ($this->sku->cost_price ?? 0),
                'minimum_stock_level' => $this->sku->minimum_stock_level ?? 0,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'ledger_entries' => $this->whenLoaded('ledgerEntries', fn () => $this->ledgerEntries->map(fn ($entry) => [
                'id'               => $entry->id,
                'transaction_type' => $entry->transaction_type,
                'quantity'         => $entry->quantity,
                'balance_before'   => $entry->balance_before,
                'balance_after'    => $entry->balance_after,
                'unit_cost'        => (float) $entry->unit_cost,
                'source_document'  => $entry->source_document,
                'reason'           => $entry->reason,
                'created_at'       => $entry->created_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
