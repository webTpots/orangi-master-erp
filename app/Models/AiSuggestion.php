<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AiSuggestion extends Model
{
    use HasFactory;

    // ── Suggestion Type Constants ────────────────────────────────────
    const TYPE_SKU_MAPPING          = 'sku_mapping';
    const TYPE_REORDER              = 'reorder';
    const TYPE_PRICE_ADJUSTMENT     = 'price_adjustment';
    const TYPE_QUALITY_ALERT        = 'quality_alert';
    const TYPE_PROCESS_IMPROVEMENT  = 'process_improvement';
    const TYPE_ANOMALY              = 'anomaly';

    const SUGGESTION_TYPES = [
        self::TYPE_SKU_MAPPING,
        self::TYPE_REORDER,
        self::TYPE_PRICE_ADJUSTMENT,
        self::TYPE_QUALITY_ALERT,
        self::TYPE_PROCESS_IMPROVEMENT,
        self::TYPE_ANOMALY,
    ];

    const TYPE_LABELS = [
        'sku_mapping'         => 'SKU Mapping',
        'reorder'             => 'Reorder',
        'price_adjustment'    => 'Price Adjustment',
        'quality_alert'       => 'Quality Alert',
        'process_improvement' => 'Process Improvement',
        'anomaly'             => 'Anomaly',
    ];

    // ── Priority Constants ───────────────────────────────────────────
    const PRIORITY_LOW      = 'low';
    const PRIORITY_MEDIUM   = 'medium';
    const PRIORITY_HIGH     = 'high';
    const PRIORITY_CRITICAL = 'critical';

    const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    // ── Status Constants ─────────────────────────────────────────────
    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED  = 'expired';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    const STATUS_LABELS = [
        'pending'  => 'Pending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired'  => 'Expired',
    ];

    protected $fillable = [
        'company_id',
        'ai_task_id',
        'suggestion_type',
        'entity_type',
        'entity_id',
        'title',
        'description',
        'confidence',
        'priority',
        'status',
        'action_data',
        'accepted_by',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence'  => 'decimal:2',
            'action_data' => 'array',
            'accepted_at' => 'datetime',
            'expires_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function aiTask(): BelongsTo
    {
        return $this->belongsTo(AiTask::class);
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Accessors ──────────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::TYPE_LABELS[$this->suggestion_type] ?? ucfirst($this->suggestion_type);
        });
    }

    protected function confidencePercent(): Attribute
    {
        return Attribute::get(function () {
            return round($this->confidence * 100);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('suggestion_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
