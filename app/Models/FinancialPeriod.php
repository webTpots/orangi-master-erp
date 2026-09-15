<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'locked_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    // ── Methods ────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->status === 'locked' || $this->locked_at !== null;
    }
}
