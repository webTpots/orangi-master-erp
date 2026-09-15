<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePerformance extends Model
{
    use HasFactory;

    protected $table = 'marketplace_performance';

    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'period_type',
        'period_date',
        'total_orders',
        'total_revenue',
        'total_commission',
        'commission_rate',
        'total_returns',
        'return_rate',
        'total_rto',
        'rto_rate',
        'net_profit',
        'avg_delivery_days',
    ];

    protected function casts(): array
    {
        return [
            'period_date'       => 'date',
            'total_orders'      => 'integer',
            'total_revenue'     => 'decimal:2',
            'total_commission'  => 'decimal:2',
            'commission_rate'   => 'decimal:2',
            'total_returns'     => 'integer',
            'return_rate'       => 'decimal:2',
            'total_rto'         => 'integer',
            'rto_rate'          => 'decimal:2',
            'net_profit'        => 'decimal:2',
            'avg_delivery_days' => 'decimal:1',
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

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('period_date', [$from, $to]);
    }
}
