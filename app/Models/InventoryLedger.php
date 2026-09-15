<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLedger extends Model
{
    use HasFactory;

    protected $table = 'inventory_ledger';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'inventory_item_id',
        'company_id',
        'sku_id',
        'warehouse_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity',
        'balance_before',
        'balance_after',
        'unit_cost',
        'total_cost',
        'source_document',
        'batch_id',
        'reason',
        'notes',
        'performed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'integer',
            'balance_before' => 'integer',
            'balance_after'  => 'integer',
            'unit_cost'      => 'decimal:4',
            'total_cost'     => 'decimal:2',
            'created_at'     => 'datetime',
        ];
    }

    // ── Boot ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $ledger) {
            $ledger->created_at = $ledger->created_at ?? now();
        });
    }

    // ── Relationships ──────────────────────────────────────────────

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
