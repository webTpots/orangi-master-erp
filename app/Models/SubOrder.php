<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SubOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'sub_order_number',
        'sku_id',
        'marketplace_sku',
        'product_name',
        'variant_description',
        'color',
        'size',
        'quantity',
        'unit_price',
        'line_total',
        'stock_status',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function shortName(): Attribute
    {
        return Attribute::get(function () {
            $parts = array_filter([
                $this->product_name ? \Illuminate\Support\Str::limit($this->product_name, 40) : null,
                $this->color,
                $this->size,
            ]);

            return implode(' / ', $parts) ?: $this->sub_order_number;
        });
    }

    protected function stockStatusLabel(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->stock_status) {
                'pending'      => 'Pending',
                'in_stock'     => 'In Stock',
                'partial'      => 'Partial',
                'out_of_stock' => 'Out of Stock',
                'not_mapped'   => 'Not Mapped',
                default        => ucfirst($this->stock_status),
            };
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
