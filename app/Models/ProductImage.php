<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'  => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
