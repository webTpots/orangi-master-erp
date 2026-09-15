<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'sku_id',
        'vendor_id',
        'current_stock',
        'daily_run_rate',
        'days_until_stockout',
        'suggested_quantity',
        'estimated_cost',
        'priority',
        'status',
        'purchase_order_id',
    ];

    protected function casts(): array
    {
        return [
            'current_stock'      => 'integer',
            'daily_run_rate'     => 'decimal:2',
            'days_until_stockout' => 'integer',
            'suggested_quantity' => 'integer',
            'estimated_cost'     => 'decimal:2',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high'   => 'warning',
            'medium' => 'brand',
            'low'    => 'info',
            default  => 'neutral',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'         => 'Pending',
            'approved'        => 'Approved',
            'converted_to_po' => 'Converted to PO',
            'dismissed'       => 'Dismissed',
            default           => ucfirst($this->status),
        };
    }
}
