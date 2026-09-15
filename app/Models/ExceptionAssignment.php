<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionAssignment extends Model
{
    use HasFactory;

    protected $table = 'exception_assignments';

    const STATUS_ASSIGNED    = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ESCALATED   = 'escalated';
    const STATUS_RESOLVED    = 'resolved';
    const STATUS_CLOSED      = 'closed';

    const STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ESCALATED,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    const PRIORITY_LOW    = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    protected $fillable = [
        'exception_id',
        'assigned_to',
        'assigned_by',
        'status',
        'priority',
        'due_at',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_at'       => 'datetime',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function exception(): BelongsTo
    {
        return $this->belongsTo(AppException::class, 'exception_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}
