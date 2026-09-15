<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseRack extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_zone_id',
        'name',
        'code',
        'capacity',
        'status',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id');
    }

    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
