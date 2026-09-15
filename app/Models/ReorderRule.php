<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'sku_id',
        'design_id',
        'vendor_id',
        'rule_type',
        'min_stock_threshold',
        'days_of_stock_threshold',
        'reorder_quantity',
        'max_order_quantity',
        'is_active',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_threshold'     => 'integer',
            'days_of_stock_threshold' => 'integer',
            'reorder_quantity'        => 'integer',
            'max_order_quantity'      => 'integer',
            'is_active'               => 'boolean',
            'last_triggered_at'       => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function getRuleTypeLabelAttribute(): string
    {
        return match ($this->rule_type) {
            'min_stock'      => 'Min Stock',
            'days_of_stock'  => 'Days of Stock',
            'forecast_based' => 'Forecast Based',
            'manual'         => 'Manual',
            default          => ucfirst($this->rule_type),
        };
    }

    public function getTargetLabelAttribute(): string
    {
        if ($this->sku_id) {
            return 'SKU: ' . ($this->sku?->sku_code ?? $this->sku_id);
        }

        if ($this->design_id) {
            return 'Design: ' . ($this->design?->name ?? $this->design_id);
        }

        return 'All SKUs';
    }
}
