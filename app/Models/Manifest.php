<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manifest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'marketplace_id',
        'import_batch_id',
        'file_name',
        'file_path',
        'file_hash',
        'manifest_date',
        'supplier_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'manifest_date' => 'date',
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

    public function picklistLines(): HasMany
    {
        return $this->hasMany(ManifestPicklistLine::class);
    }

    public function shipmentLines(): HasMany
    {
        return $this->hasMany(ManifestShipmentLine::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
