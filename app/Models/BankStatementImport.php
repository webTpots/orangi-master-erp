<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankStatementImport extends Model
{
    use HasFactory;

    // ── Format Constants ──────────────────────────────────────────
    const FORMAT_CSV   = 'csv';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_OFX   = 'ofx';
    const FORMAT_PDF   = 'pdf';

    const FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_EXCEL,
        self::FORMAT_OFX,
        self::FORMAT_PDF,
    ];

    const FORMAT_LABELS = [
        'csv'   => 'CSV',
        'excel' => 'Excel',
        'ofx'   => 'OFX',
        'pdf'   => 'PDF',
    ];

    // ── Status Constants ──────────────────────────────────────────
    const STATUS_UPLOADED            = 'uploaded';
    const STATUS_PROCESSING          = 'processing';
    const STATUS_COMPLETED           = 'completed';
    const STATUS_FAILED              = 'failed';
    const STATUS_PARTIALLY_COMPLETED = 'partially_completed';

    const STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_PARTIALLY_COMPLETED,
    ];

    const STATUS_LABELS = [
        'uploaded'            => 'Uploaded',
        'processing'          => 'Processing',
        'completed'           => 'Completed',
        'failed'              => 'Failed',
        'partially_completed' => 'Partially Completed',
    ];

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'file_name',
        'file_path',
        'file_hash',
        'format',
        'period_start',
        'period_end',
        'total_records',
        'imported_records',
        'duplicate_records',
        'status',
        'error_message',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'     => 'date',
            'period_end'       => 'date',
            'total_records'    => 'integer',
            'imported_records' => 'integer',
            'duplicate_records' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function importedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
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
}
