<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    // ── Payment Type Constants ──────────────────────────────────
    const TYPE_MARKETPLACE_SETTLEMENT = 'marketplace_settlement';
    const TYPE_CUSTOMER_COD           = 'customer_cod';
    const TYPE_REFUND                 = 'refund';
    const TYPE_ADVANCE                = 'advance';
    const TYPE_OTHER                  = 'other';

    const PAYMENT_TYPES = [
        self::TYPE_MARKETPLACE_SETTLEMENT,
        self::TYPE_CUSTOMER_COD,
        self::TYPE_REFUND,
        self::TYPE_ADVANCE,
        self::TYPE_OTHER,
    ];

    const PAYMENT_TYPE_LABELS = [
        'marketplace_settlement' => 'Marketplace Settlement',
        'customer_cod'           => 'Customer COD',
        'refund'                 => 'Refund',
        'advance'                => 'Advance',
        'other'                  => 'Other',
    ];

    // ── Payment Method Constants ────────────────────────────────
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_UPI           = 'upi';
    const METHOD_NEFT          = 'neft';
    const METHOD_RTGS          = 'rtgs';
    const METHOD_CHEQUE        = 'cheque';
    const METHOD_CASH          = 'cash';
    const METHOD_OTHER         = 'other';

    const PAYMENT_METHODS = [
        self::METHOD_BANK_TRANSFER,
        self::METHOD_UPI,
        self::METHOD_NEFT,
        self::METHOD_RTGS,
        self::METHOD_CHEQUE,
        self::METHOD_CASH,
        self::METHOD_OTHER,
    ];

    const PAYMENT_METHOD_LABELS = [
        'bank_transfer' => 'Bank Transfer',
        'upi'           => 'UPI',
        'neft'          => 'NEFT',
        'rtgs'          => 'RTGS',
        'cheque'        => 'Cheque',
        'cash'          => 'Cash',
        'other'         => 'Other',
    ];

    // ── Status Constants ────────────────────────────────────────
    const STATUS_PENDING   = 'pending';
    const STATUS_RECEIVED  = 'received';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_BOUNCED   = 'bounced';
    const STATUS_REVERSED  = 'reversed';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RECEIVED,
        self::STATUS_CONFIRMED,
        self::STATUS_BOUNCED,
        self::STATUS_REVERSED,
    ];

    const STATUS_LABELS = [
        'pending'   => 'Pending',
        'received'  => 'Received',
        'confirmed' => 'Confirmed',
        'bounced'   => 'Bounced',
        'reversed'  => 'Reversed',
    ];

    protected $fillable = [
        'company_id',
        'settlement_id',
        'payment_type',
        'payment_method',
        'amount',
        'currency',
        'reference_number',
        'bank_reference',
        'paid_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    protected function paymentTypeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::PAYMENT_TYPE_LABELS[$this->payment_type] ?? ucfirst($this->payment_type);
        });
    }

    protected function paymentMethodLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? ucfirst($this->payment_method);
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
