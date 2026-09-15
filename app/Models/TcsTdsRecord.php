<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TcsTdsRecord extends Model
{
    use HasFactory;

    protected $table = 'tcs_tds_records';

    // ── Record Type Constants ───────────────────────────────────
    const TYPE_TCS = 'tcs';
    const TYPE_TDS = 'tds';

    const RECORD_TYPES = [
        self::TYPE_TCS,
        self::TYPE_TDS,
    ];

    const RECORD_TYPE_LABELS = [
        'tcs' => 'TCS',
        'tds' => 'TDS',
    ];

    // ── Status Constants ────────────────────────────────────────
    const STATUS_COMPUTED             = 'computed';
    const STATUS_FILED                = 'filed';
    const STATUS_CERTIFICATE_RECEIVED = 'certificate_received';

    const STATUSES = [
        self::STATUS_COMPUTED,
        self::STATUS_FILED,
        self::STATUS_CERTIFICATE_RECEIVED,
    ];

    const STATUS_LABELS = [
        'computed'             => 'Computed',
        'filed'                => 'Filed',
        'certificate_received' => 'Certificate Received',
    ];

    protected $fillable = [
        'company_id',
        'record_type',
        'marketplace_account_id',
        'settlement_id',
        'section_code',
        'financial_year',
        'quarter',
        'gross_amount',
        'rate',
        'amount',
        'certificate_number',
        'certificate_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount'     => 'decimal:2',
            'rate'             => 'decimal:2',
            'amount'           => 'decimal:2',
            'certificate_date' => 'date',
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

    protected function recordTypeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::RECORD_TYPE_LABELS[$this->record_type] ?? strtoupper($this->record_type);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeTcs($query)
    {
        return $query->where('record_type', self::TYPE_TCS);
    }

    public function scopeTds($query)
    {
        return $query->where('record_type', self::TYPE_TDS);
    }
}
