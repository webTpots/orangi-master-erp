<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'marketplace_id',
        'import_batch_id',
        'file_name',
        'file_path',
        'file_hash',
        'total_pages',
        'parsed_labels',
        'linked_labels',
        'error_labels',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_pages'    => 'integer',
            'parsed_labels'  => 'integer',
            'linked_labels'  => 'integer',
            'error_labels'   => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
