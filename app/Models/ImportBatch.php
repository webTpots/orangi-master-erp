<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'uploaded_by',
        'type',
        'file_path',
        'original_filename',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'status',
        'error_summary',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_records'      => 'integer',
            'processed_records'  => 'integer',
            'successful_records' => 'integer',
            'failed_records'     => 'integer',
            'started_at'         => 'datetime',
            'completed_at'       => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ImportRecord::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
