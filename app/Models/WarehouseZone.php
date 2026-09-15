<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class WarehouseZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'description',
        'zone_type',
        'status',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class);
    }

    public function bins(): HasManyThrough
    {
        return $this->hasManyThrough(WarehouseBin::class, WarehouseRack::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
