<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ReconciliationRule extends Model
{
    use HasFactory;

    // ── Match Field Constants ─────────────────────────────────────
    const FIELD_REFERENCE_NUMBER = 'reference_number';
    const FIELD_DESCRIPTION      = 'description';
    const FIELD_AMOUNT           = 'amount';
    const FIELD_COMBINATION      = 'combination';

    const MATCH_FIELDS = [
        self::FIELD_REFERENCE_NUMBER,
        self::FIELD_DESCRIPTION,
        self::FIELD_AMOUNT,
        self::FIELD_COMBINATION,
    ];

    const MATCH_FIELD_LABELS = [
        'reference_number' => 'Reference Number',
        'description'      => 'Description',
        'amount'           => 'Amount',
        'combination'      => 'Combination',
    ];

    // ── Category Constants ────────────────────────────────────────
    const CATEGORY_LABELS = [
        'marketplace_settlement' => 'Marketplace Settlement',
        'vendor_payment'         => 'Vendor Payment',
        'refund'                 => 'Refund',
        'expense'                => 'Expense',
        'salary'                 => 'Salary',
        'tax'                    => 'Tax',
        'transfer'               => 'Transfer',
        'other'                  => 'Other',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'match_field',
        'match_pattern',
        'match_entity_type',
        'category',
        'priority',
        'is_active',
        'auto_match',
    ];

    protected function casts(): array
    {
        return [
            'priority'   => 'integer',
            'is_active'  => 'boolean',
            'auto_match' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function matchFieldLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::MATCH_FIELD_LABELS[$this->match_field] ?? ucfirst($this->match_field);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoMatch($query)
    {
        return $query->where('auto_match', true);
    }
}
