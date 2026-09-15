<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Marketplace extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config'    => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function accounts(): HasMany
    {
        return $this->hasMany(MarketplaceAccount::class);
    }

    public function marketplaceConfigs(): HasMany
    {
        return $this->hasMany(MarketplaceConfig::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function cutoffTime(): Attribute
    {
        return Attribute::get(function () {
            return $this->config['cutoff_time'] ?? null;
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
