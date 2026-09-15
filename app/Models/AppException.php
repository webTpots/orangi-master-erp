<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppException extends Model
{
    use HasFactory;

    protected $table = 'exceptions';

    const STATUS_OPEN      = 'open';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_RESOLVED  = 'resolved';
    const STATUS_IGNORED   = 'ignored';

    const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_IGNORED,
    ];

    const STATUS_LABELS = [
        'open'      => 'Open',
        'in_review' => 'In Review',
        'resolved'  => 'Resolved',
        'ignored'   => 'Ignored',
    ];

    const SEVERITY_LOW      = 'low';
    const SEVERITY_MEDIUM   = 'medium';
    const SEVERITY_HIGH     = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    const SEVERITY_LABELS = [
        'low'      => 'Low',
        'medium'   => 'Medium',
        'high'     => 'High',
        'critical' => 'Critical',
    ];

    protected $fillable = [
        'company_id',
        'category_id',
        'entity_type',
        'entity_id',
        'exception_type',
        'severity',
        'title',
        'description',
        'resolution',
        'status',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExceptionCategory::class, 'category_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExceptionAssignment::class, 'exception_id');
    }

    public function latestAssignment(): HasOne
    {
        return $this->hasOne(ExceptionAssignment::class, 'exception_id')->latestOfMany();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ExceptionComment::class, 'exception_id')->orderByDesc('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_REVIEW]);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('exception_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Methods ────────────────────────────────────────────────────

    /**
     * Check if the exception has breached its SLA.
     */
    public function isSlaBreached(): bool
    {
        if (! $this->category || ! $this->category->sla_hours) {
            return false;
        }

        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_IGNORED])) {
            return false;
        }

        return $this->created_at->addHours($this->category->sla_hours)->isPast();
    }

    /**
     * Get SLA remaining hours (negative means breached).
     */
    public function slaRemainingHours(): ?float
    {
        if (! $this->category || ! $this->category->sla_hours) {
            return null;
        }

        $deadline = $this->created_at->addHours($this->category->sla_hours);

        return round(now()->diffInMinutes($deadline, false) / 60, 1);
    }
}
