<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingData extends Model
{
    use HasFactory;

    protected $table = 'ai_training_data';

    // ── Data Type Constants ──────────────────────────────────────────
    const TYPE_SKU_MAPPING_FEEDBACK       = 'sku_mapping_feedback';
    const TYPE_CLASSIFICATION_CORRECTION  = 'classification_correction';
    const TYPE_ANOMALY_FEEDBACK           = 'anomaly_feedback';

    const DATA_TYPES = [
        self::TYPE_SKU_MAPPING_FEEDBACK,
        self::TYPE_CLASSIFICATION_CORRECTION,
        self::TYPE_ANOMALY_FEEDBACK,
    ];

    protected $fillable = [
        'company_id',
        'data_type',
        'input_data',
        'expected_output',
        'actual_output',
        'is_correct',
        'feedback_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'input_data'      => 'array',
            'expected_output' => 'array',
            'actual_output'   => 'array',
            'is_correct'      => 'boolean',
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

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('data_type', $type);
    }
}
