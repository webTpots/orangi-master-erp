<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'sku_id',
        'quantity_requested',
        'quantity_confirmed',
        'quantity_received',
        'unit_cost',
        'line_total',
        'status',
        'vendor_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested'  => 'integer',
            'quantity_confirmed'  => 'integer',
            'quantity_received'   => 'integer',
            'unit_cost'           => 'decimal:4',
            'line_total'          => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function goodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class, 'po_line_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    protected function shortage(): Attribute
    {
        return Attribute::get(function () {
            return max(0, $this->quantity_requested - $this->quantity_confirmed);
        });
    }

    protected function pendingReceive(): Attribute
    {
        return Attribute::get(function () {
            return max(0, $this->quantity_confirmed - $this->quantity_received);
        });
    }
}
