<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'po_line_id',
        'sku_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_accepted' => 'integer',
            'quantity_rejected' => 'integer',
            'unit_cost'         => 'decimal:4',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function poLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'po_line_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
