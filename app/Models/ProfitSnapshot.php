<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'period_type',
        'period_date',
        'total_revenue',
        'total_cost_of_goods',
        'total_marketplace_commission',
        'total_shipping_cost',
        'total_returns_cost',
        'total_penalties',
        'total_other_expenses',
        'gross_profit',
        'net_profit',
        'profit_margin',
        'total_orders',
        'total_units',
        'average_order_value',
        'return_rate',
        'rto_rate',
    ];

    protected function casts(): array
    {
        return [
            'period_date'                  => 'date',
            'total_revenue'                => 'decimal:2',
            'total_cost_of_goods'          => 'decimal:2',
            'total_marketplace_commission' => 'decimal:2',
            'total_shipping_cost'          => 'decimal:2',
            'total_returns_cost'           => 'decimal:2',
            'total_penalties'              => 'decimal:2',
            'total_other_expenses'         => 'decimal:2',
            'gross_profit'                 => 'decimal:2',
            'net_profit'                   => 'decimal:2',
            'profit_margin'                => 'decimal:2',
            'total_orders'                 => 'integer',
            'total_units'                  => 'integer',
            'average_order_value'          => 'decimal:2',
            'return_rate'                  => 'decimal:2',
            'rto_rate'                     => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function scopeDaily($query)
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
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
