<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifestPicklistLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'sku',
        'color',
        'size',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }
}
