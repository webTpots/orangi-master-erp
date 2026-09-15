<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'company_id',
        'config_key',
        'config_value',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForMarketplace($query, $marketplaceId)
    {
        return $query->where('marketplace_id', $marketplaceId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
