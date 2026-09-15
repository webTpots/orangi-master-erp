<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScanLog extends Model
{
    use HasFactory;

    /**
     * Immutable: no updated_at column.
     */
    const UPDATED_AT = null;

    // ── Scan Type Constants ──────────────────────────────────────
    const TYPE_PACK           = 'pack';
    const TYPE_DISPATCH       = 'dispatch';
    const TYPE_RECEIVE        = 'receive';
    const TYPE_RETURN_RECEIVE = 'return_receive';
    const TYPE_QUALITY_CHECK  = 'quality_check';
    const TYPE_SHELF_ASSIGN   = 'shelf_assign';

    const SCAN_TYPES = [
        self::TYPE_PACK,
        self::TYPE_DISPATCH,
        self::TYPE_RECEIVE,
        self::TYPE_RETURN_RECEIVE,
        self::TYPE_QUALITY_CHECK,
        self::TYPE_SHELF_ASSIGN,
    ];

    const SCAN_TYPE_LABELS = [
        'pack'           => 'Pack',
        'dispatch'       => 'Dispatch',
        'receive'        => 'Receive',
        'return_receive' => 'Return Receive',
        'quality_check'  => 'Quality Check',
        'shelf_assign'   => 'Shelf Assign',
    ];

    // ── Scan Method Constants ────────────────────────────────────
    const METHOD_BARCODE = 'barcode';
    const METHOD_QR      = 'qr';
    const METHOD_MANUAL  = 'manual';

    const SCAN_METHODS = [
        self::METHOD_BARCODE,
        self::METHOD_QR,
        self::METHOD_MANUAL,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'scannable_type',
        'scannable_id',
        'scan_type',
        'barcode_data',
        'scan_method',
        'scanned_at',
        'location',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scannable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getScanTypeLabelAttribute(): string
    {
        return self::SCAN_TYPE_LABELS[$this->scan_type] ?? ucfirst($this->scan_type);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderByDesc('scanned_at')->limit($limit);
    }
}
