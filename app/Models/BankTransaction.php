<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankTransaction extends Model
{
    use HasFactory;

    // ── Transaction Type Constants ────────────────────────────────
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT  = 'debit';

    // ── Category Constants ────────────────────────────────────────
    const CATEGORY_MARKETPLACE_SETTLEMENT = 'marketplace_settlement';
    const CATEGORY_VENDOR_PAYMENT         = 'vendor_payment';
    const CATEGORY_REFUND                 = 'refund';
    const CATEGORY_EXPENSE                = 'expense';
    const CATEGORY_SALARY                 = 'salary';
    const CATEGORY_TAX                    = 'tax';
    const CATEGORY_TRANSFER               = 'transfer';
    const CATEGORY_OTHER                  = 'other';
    const CATEGORY_UNCATEGORIZED          = 'uncategorized';

    const CATEGORIES = [
        self::CATEGORY_MARKETPLACE_SETTLEMENT,
        self::CATEGORY_VENDOR_PAYMENT,
        self::CATEGORY_REFUND,
        self::CATEGORY_EXPENSE,
        self::CATEGORY_SALARY,
        self::CATEGORY_TAX,
        self::CATEGORY_TRANSFER,
        self::CATEGORY_OTHER,
        self::CATEGORY_UNCATEGORIZED,
    ];

    const CATEGORY_LABELS = [
        'marketplace_settlement' => 'Marketplace Settlement',
        'vendor_payment'         => 'Vendor Payment',
        'refund'                 => 'Refund',
        'expense'                => 'Expense',
        'salary'                 => 'Salary',
        'tax'                    => 'Tax',
        'transfer'               => 'Transfer',
        'other'                  => 'Other',
        'uncategorized'          => 'Uncategorized',
    ];

    // ── Match Status Constants ────────────────────────────────────
    const MATCH_UNMATCHED        = 'unmatched';
    const MATCH_AUTO_MATCHED     = 'auto_matched';
    const MATCH_MANUALLY_MATCHED = 'manually_matched';
    const MATCH_DISPUTED         = 'disputed';
    const MATCH_IGNORED          = 'ignored';

    const MATCH_STATUSES = [
        self::MATCH_UNMATCHED,
        self::MATCH_AUTO_MATCHED,
        self::MATCH_MANUALLY_MATCHED,
        self::MATCH_DISPUTED,
        self::MATCH_IGNORED,
    ];

    const MATCH_STATUS_LABELS = [
        'unmatched'        => 'Unmatched',
        'auto_matched'     => 'Auto Matched',
        'manually_matched' => 'Manually Matched',
        'disputed'         => 'Disputed',
        'ignored'          => 'Ignored',
    ];

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'transaction_date',
        'value_date',
        'description',
        'reference_number',
        'transaction_type',
        'amount',
        'running_balance',
        'category',
        'match_status',
        'matched_entity_type',
        'matched_entity_id',
        'matched_at',
        'matched_by',
        'import_batch_id',
        'raw_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'value_date'       => 'date',
            'amount'           => 'decimal:2',
            'running_balance'  => 'decimal:2',
            'matched_at'       => 'datetime',
            'raw_data'         => 'array',
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

    public function matchedEntity(): MorphTo
    {
        return $this->morphTo('matched_entity', 'matched_entity_type', 'matched_entity_id');
    }

    public function matchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function matchStatusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::MATCH_STATUS_LABELS[$this->match_status] ?? ucfirst($this->match_status);
        });
    }

    protected function categoryLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::CATEGORY_LABELS[$this->category] ?? ucfirst($this->category);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('match_status', self::MATCH_UNMATCHED);
    }

    public function scopeMatched($query)
    {
        return $query->whereIn('match_status', [self::MATCH_AUTO_MATCHED, self::MATCH_MANUALLY_MATCHED]);
    }

    public function scopeCredits($query)
    {
        return $query->where('transaction_type', self::TYPE_CREDIT);
    }

    public function scopeDebits($query)
    {
        return $query->where('transaction_type', self::TYPE_DEBIT);
    }
}
