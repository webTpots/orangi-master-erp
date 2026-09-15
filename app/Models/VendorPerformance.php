<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPerformance extends Model
{
    use HasFactory;

    protected $table = 'vendor_performance';

    protected $fillable = [
        'company_id',
        'vendor_id',
        'period_type',
        'period_date',
        'total_pos',
        'total_units_ordered',
        'total_units_received',
        'fulfillment_rate',
        'avg_lead_time_days',
        'total_amount',
        'quality_return_rate',
        'on_time_delivery_rate',
    ];

    protected function casts(): array
    {
        return [
            'period_date'          => 'date',
            'total_pos'            => 'integer',
            'total_units_ordered'  => 'integer',
            'total_units_received' => 'integer',
            'fulfillment_rate'     => 'decimal:2',
            'avg_lead_time_days'   => 'decimal:1',
            'total_amount'         => 'decimal:2',
            'quality_return_rate'  => 'decimal:2',
            'on_time_delivery_rate' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
