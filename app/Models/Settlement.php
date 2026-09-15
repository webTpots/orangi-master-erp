<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Settlement extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status Constants ─────────────────────────────────────────
    const STATUS_IMPORTED              = 'imported';
    const STATUS_PROCESSING            = 'processing';
    const STATUS_RECONCILED            = 'reconciled';
    const STATUS_PARTIALLY_RECONCILED  = 'partially_reconciled';
    const STATUS_DISPUTED              = 'disputed';
    const STATUS_CLOSED                = 'closed';

    const STATUSES = [
        self::STATUS_IMPORTED,
        self::STATUS_PROCESSING,
        self::STATUS_RECONCILED,
        self::STATUS_PARTIALLY_RECONCILED,
        self::STATUS_DISPUTED,
        self::STATUS_CLOSED,
    ];

    const STATUS_LABELS = [
        'imported'              => 'Imported',
        'processing'            => 'Processing',
        'reconciled'            => 'Reconciled',
        'partially_reconciled'  => 'Partially Reconciled',
        'disputed'              => 'Disputed',
        'closed'                => 'Closed',
    ];

    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'settlement_reference',
        'settlement_date',
        'period_start',
        'period_end',
        'total_order_amount',
        'total_shipping_fee',
        'total_commission',
        'total_tcs',
        'total_tds',
        'total_penalty',
        'total_other_deductions',
        'net_payable',
        'total_orders',
        'status',
        'imported_at',
        'file_path',
        'file_hash',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date'       => 'date',
            'period_start'          => 'date',
            'period_end'            => 'date',
            'total_order_amount'    => 'decimal:2',
            'total_shipping_fee'    => 'decimal:2',
            'total_commission'      => 'decimal:2',
            'total_tcs'             => 'decimal:2',
            'total_tds'             => 'decimal:2',
            'total_penalty'         => 'decimal:2',
            'total_other_deductions' => 'decimal:2',
            'net_payable'           => 'decimal:2',
            'total_orders'          => 'integer',
            'imported_at'           => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SettlementLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
