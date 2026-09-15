<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignPerformance extends Model
{
    use HasFactory;

    protected $table = 'design_performance';

    protected $fillable = [
        'company_id',
        'design_id',
        'period_type',
        'period_date',
        'units_sold',
        'units_returned',
        'revenue',
        'cost',
        'profit',
        'profit_margin',
        'return_rate',
        'avg_selling_price',
        'stock_remaining',
        'days_of_stock',
    ];

    protected function casts(): array
    {
        return [
            'period_date'       => 'date',
            'units_sold'        => 'integer',
            'units_returned'    => 'integer',
            'revenue'           => 'decimal:2',
            'cost'              => 'decimal:2',
            'profit'            => 'decimal:2',
            'profit_margin'     => 'decimal:2',
            'return_rate'       => 'decimal:2',
            'avg_selling_price' => 'decimal:2',
            'stock_remaining'   => 'integer',
            'days_of_stock'     => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
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
