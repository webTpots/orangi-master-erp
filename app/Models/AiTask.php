<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AiTask extends Model
{
    use HasFactory;

    // ── Task Type Constants ──────────────────────────────────────────
    const TYPE_DOCUMENT_PARSE   = 'document_parse';
    const TYPE_SKU_MATCH        = 'sku_match';
    const TYPE_ANOMALY_DETECT   = 'anomaly_detect';
    const TYPE_DEMAND_FORECAST  = 'demand_forecast';
    const TYPE_IMAGE_CLASSIFY   = 'image_classify';
    const TYPE_SMART_SUGGEST    = 'smart_suggest';

    const TASK_TYPES = [
        self::TYPE_DOCUMENT_PARSE,
        self::TYPE_SKU_MATCH,
        self::TYPE_ANOMALY_DETECT,
        self::TYPE_DEMAND_FORECAST,
        self::TYPE_IMAGE_CLASSIFY,
        self::TYPE_SMART_SUGGEST,
    ];

    const TYPE_LABELS = [
        'document_parse'  => 'Document Parse',
        'sku_match'       => 'SKU Match',
        'anomaly_detect'  => 'Anomaly Detection',
        'demand_forecast' => 'Demand Forecast',
        'image_classify'  => 'Image Classify',
        'smart_suggest'   => 'Smart Suggest',
    ];

    // ── Status Constants ─────────────────────────────────────────────
    const STATUS_QUEUED     = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_CANCELLED  = 'cancelled';

    const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    const STATUS_LABELS = [
        'queued'     => 'Queued',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'failed'     => 'Failed',
        'cancelled'  => 'Cancelled',
    ];

    protected $fillable = [
        'company_id',
        'task_type',
        'status',
        'input_data',
        'output_data',
        'confidence_score',
        'model_used',
        'processing_time_ms',
        'error_message',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'input_data'       => 'array',
            'output_data'      => 'array',
            'confidence_score' => 'decimal:2',
            'processed_at'     => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
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
            return self::TYPE_LABELS[$this->task_type] ?? ucfirst($this->task_type);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('task_type', $type);
    }
}
