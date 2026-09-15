<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_id',
        'company_id',
        'sku_code',
        'barcode',
        'master_sku_code',
        'selling_price',
        'cost_price',
        'mrp',
        'minimum_stock_level',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price'    => 'decimal:2',
            'mrp'           => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function skuMappings(): HasMany
    {
        return $this->hasMany(SkuMapping::class);
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function shortName(): Attribute
    {
        return Attribute::get(function () {
            $design = $this->variant?->product?->design?->name;
            $color  = $this->variant?->color;
            $size   = $this->variant?->size;

            return implode(' · ', array_filter([$design, $color, $size]));
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
