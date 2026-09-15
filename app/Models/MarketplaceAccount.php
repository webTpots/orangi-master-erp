<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'company_id',
        'account_name',
        'seller_id',
        'credentials',
        'webhook_secret',
        'is_active',
        'last_synced_at',
        'status',
    ];

    protected $hidden = [
        'credentials',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'credentials'   => 'encrypted:array',
            'is_active'     => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function skuMappings(): HasMany
    {
        return $this->hasMany(SkuMapping::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
