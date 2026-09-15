<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuMapping extends Model
{
    protected $fillable = [
        'sku_id', 'company_id', 'external_identifier', 'external_name',
        'source_type', 'source_id', 'marketplace_id', 'vendor_id',
        'confidence_score', 'mapping_status', 'mapped_by',
    ];

    protected function casts(): array
    {
        return ['confidence_score' => 'decimal:2'];
    }

    public function sku(): BelongsTo { return $this->belongsTo(Sku::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function mapper(): BelongsTo { return $this->belongsTo(User::class, 'mapped_by'); }

    public function scopeConfirmed($query) { return $query->where('mapping_status', 'confirmed'); }
    public function scopeSuggested($query) { return $query->where('mapping_status', 'suggested'); }
}
