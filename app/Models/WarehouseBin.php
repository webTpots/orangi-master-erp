<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseBin extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_rack_id',
        'name',
        'code',
        'bin_type',
        'capacity',
        'status',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function rack(): BelongsTo
    {
        return $this->belongsTo(WarehouseRack::class, 'warehouse_rack_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'bin_id');
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getFullLocationAttribute(): string
    {
        return implode(' > ', array_filter([
            $this->rack?->zone?->warehouse?->name,
            $this->rack?->zone?->name,
            $this->rack?->name,
            $this->name,
        ]));
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
