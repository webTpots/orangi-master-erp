<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'return_id',
        'sku_id',
        'variant_id',
        'quantity',
        'condition',
        'restock_quantity',
        'dispose_quantity',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'integer',
            'restock_quantity' => 'integer',
            'dispose_quantity' => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    // ── Condition Constants ───────────────────────────────────────

    const CONDITION_LABELS = [
        'like_new'     => 'Like New',
        'good'         => 'Good',
        'minor_damage' => 'Minor Damage',
        'major_damage' => 'Major Damage',
        'unsellable'   => 'Unsellable',
        'missing'      => 'Missing',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class, 'return_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
