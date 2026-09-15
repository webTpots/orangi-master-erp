<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GstEntry extends Model
{
    use HasFactory;

    // ── GST Type Constants ──────────────────────────────────────
    const TYPE_SALE        = 'sale';
    const TYPE_PURCHASE    = 'purchase';
    const TYPE_CREDIT_NOTE = 'credit_note';
    const TYPE_DEBIT_NOTE  = 'debit_note';

    const GST_TYPES = [
        self::TYPE_SALE,
        self::TYPE_PURCHASE,
        self::TYPE_CREDIT_NOTE,
        self::TYPE_DEBIT_NOTE,
    ];

    const GST_TYPE_LABELS = [
        'sale'        => 'Sale',
        'purchase'    => 'Purchase',
        'credit_note' => 'Credit Note',
        'debit_note'  => 'Debit Note',
    ];

    // ── Status Constants ────────────────────────────────────────
    const STATUS_DRAFT     = 'draft';
    const STATUS_FILED     = 'filed';
    const STATUS_AMENDED   = 'amended';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_FILED,
        self::STATUS_AMENDED,
        self::STATUS_CANCELLED,
    ];

    const STATUS_LABELS = [
        'draft'     => 'Draft',
        'filed'     => 'Filed',
        'amended'   => 'Amended',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'gst_type',
        'place_of_supply',
        'hsn_code',
        'taxable_amount',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'igst_rate',
        'igst_amount',
        'total_gst',
        'total_amount',
        'financial_period_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'   => 'date',
            'taxable_amount' => 'decimal:2',
            'cgst_rate'      => 'decimal:2',
            'cgst_amount'    => 'decimal:2',
            'sgst_rate'      => 'decimal:2',
            'sgst_amount'    => 'decimal:2',
            'igst_rate'      => 'decimal:2',
            'igst_amount'    => 'decimal:2',
            'total_gst'      => 'decimal:2',
            'total_amount'   => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    protected function gstTypeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::GST_TYPE_LABELS[$this->gst_type] ?? ucfirst($this->gst_type);
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

    public function scopeForPeriod($query, string $period)
    {
        // period format: YYYY-MM
        $parts = explode('-', $period);
        if (count($parts) === 2) {
            return $query->whereYear('invoice_date', $parts[0])
                         ->whereMonth('invoice_date', $parts[1]);
        }
        return $query;
    }
}
