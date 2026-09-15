<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    // ── Account Type Constants ────────────────────────────────────
    const TYPE_CURRENT  = 'current';
    const TYPE_SAVINGS  = 'savings';
    const TYPE_OVERDRAFT = 'overdraft';

    const ACCOUNT_TYPES = [
        self::TYPE_CURRENT,
        self::TYPE_SAVINGS,
        self::TYPE_OVERDRAFT,
    ];

    const ACCOUNT_TYPE_LABELS = [
        'current'   => 'Current Account',
        'savings'   => 'Savings Account',
        'overdraft' => 'Overdraft Account',
    ];

    // ── Status Constants ──────────────────────────────────────────
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_CLOSED   = 'closed';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_CLOSED,
    ];

    const STATUS_LABELS = [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'closed'   => 'Closed',
    ];

    protected $fillable = [
        'company_id',
        'account_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'branch',
        'account_type',
        'opening_balance',
        'current_balance',
        'currency',
        'is_primary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_primary'      => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function statementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    protected function accountTypeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::ACCOUNT_TYPE_LABELS[$this->account_type] ?? ucfirst($this->account_type);
        });
    }

    protected function maskedAccountNumber(): Attribute
    {
        return Attribute::get(function () {
            return str_repeat('*', max(0, strlen($this->account_number) - 4)) . substr($this->account_number, -4);
        });
    }

    // ── Balance Methods ────────────────────────────────────────────

    /**
     * Calculate balance from opening balance + transactions.
     */
    public function getCalculatedBalance(): float
    {
        $credits = $this->transactions()->where('transaction_type', 'credit')->sum('amount');
        $debits  = $this->transactions()->where('transaction_type', 'debit')->sum('amount');

        return (float) $this->opening_balance + $credits - $debits;
    }

    /**
     * Recalculate and update the current_balance field.
     */
    public function recalculateBalance(): void
    {
        $this->update(['current_balance' => $this->getCalculatedBalance()]);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
