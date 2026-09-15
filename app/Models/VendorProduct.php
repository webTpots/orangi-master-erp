<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'sku_id',
        'vendor_sku_code',
        'vendor_product_name',
        'cost_price',
        'lead_time_days',
        'minimum_order_qty',
        'is_preferred',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price'        => 'decimal:2',
            'lead_time_days'    => 'integer',
            'minimum_order_qty' => 'integer',
            'is_preferred'      => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
