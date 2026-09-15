<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCalendar extends Model
{
    use HasFactory;

    protected $table = 'business_calendar';

    protected $fillable = [
        'company_id',
        'date',
        'day_type',
        'is_working_day',
        'is_marketplace_holiday',
        'is_warehouse_holiday',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date'                   => 'date',
            'is_working_day'         => 'boolean',
            'is_marketplace_holiday' => 'boolean',
            'is_warehouse_holiday'   => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeWorkingDays($query)
    {
        return $query->where('is_working_day', true);
    }

    public function scopeHolidays($query)
    {
        return $query->where('is_working_day', false);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
