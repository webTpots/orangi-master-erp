<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SettlementLine extends Model
{
    use HasFactory;

    // ── Settlement Type Constants ────────────────────────────────
    const TYPE_SALE         = 'sale';
    const TYPE_RETURN       = 'return';
    const TYPE_ADJUSTMENT   = 'adjustment';
    const TYPE_PENALTY      = 'penalty';
    const TYPE_COMPENSATION = 'compensation';
    const TYPE_OTHER        = 'other';

    const SETTLEMENT_TYPES = [
        self::TYPE_SALE,
        self::TYPE_RETURN,
        self::TYPE_ADJUSTMENT,
        self::TYPE_PENALTY,
        self::TYPE_COMPENSATION,
        self::TYPE_OTHER,
    ];

    // ── Match Status Constants ──────────────────────────────────
    const MATCH_MATCHED   = 'matched';
    const MATCH_UNMATCHED = 'unmatched';
    const MATCH_DISPUTED  = 'disputed';
    const MATCH_IGNORED   = 'ignored';

    const MATCH_STATUSES = [
        self::MATCH_MATCHED,
        self::MATCH_UNMATCHED,
        self::MATCH_DISPUTED,
        self::MATCH_IGNORED,
    ];

    const MATCH_STATUS_LABELS = [
        'matched'   => 'Matched',
        'unmatched' => 'Unmatched',
        'disputed'  => 'Disputed',
        'ignored'   => 'Ignored',
    ];

    protected $fillable = [
        'settlement_id',
        'order_id',
        'marketplace_order_id',
        'sub_order_id',
        'product_name',
        'sku_code',
        'quantity',
        'selling_price',
        'shipping_fee',
        'marketplace_commission',
        'tcs_amount',
        'tds_amount',
        'penalty_amount',
        'other_deductions',
        'net_amount',
        'settlement_type',
        'match_status',
        'match_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'               => 'integer',
            'selling_price'          => 'decimal:2',
            'shipping_fee'           => 'decimal:2',
            'marketplace_commission' => 'decimal:2',
            'tcs_amount'             => 'decimal:2',
            'tds_amount'             => 'decimal:2',
            'penalty_amount'         => 'decimal:2',
            'other_deductions'       => 'decimal:2',
            'net_amount'             => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function matchStatusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::MATCH_STATUS_LABELS[$this->match_status] ?? ucfirst($this->match_status);
        });
    }
}
