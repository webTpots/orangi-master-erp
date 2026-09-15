<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku_id',
        'warehouse_id',
        'company_id',
        'bin_id',
        'physical_stock',
        'reserved_stock',
        'available_stock',
        'damaged_stock',
        'blocked_stock',
        'in_transit_stock',
        'unit_cost',
        'total_value',
        'costing_method',
        'last_counted_at',
    ];

    protected function casts(): array
    {
        return [
            'physical_stock'   => 'integer',
            'reserved_stock'   => 'integer',
            'available_stock'  => 'integer',
            'damaged_stock'    => 'integer',
            'blocked_stock'    => 'integer',
            'in_transit_stock' => 'integer',
            'unit_cost'        => 'decimal:4',
            'total_value'      => 'decimal:2',
            'last_counted_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(WarehouseBin::class, 'bin_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(InventoryLedger::class);
    }

    // ── Methods ────────────────────────────────────────────────────

    public function recalculateAvailable(): self
    {
        $this->available_stock = $this->physical_stock
            - $this->reserved_stock
            - $this->damaged_stock
            - $this->blocked_stock;

        return $this;
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('available_stock', '<=', 'skus.minimum_stock_level')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('skus.minimum_stock_level', '>', 0)
            ->select('inventory_items.*');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_stock', '<=', 0);
    }
}
