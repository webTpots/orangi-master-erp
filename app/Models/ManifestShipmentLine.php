<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifestShipmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'courier',
        'supplier',
        'serial_number',
        'sub_order_number',
        'awb',
        'sku',
        'quantity',
        'size',
        'packed_status',
    ];

    protected function casts(): array
    {
        return [
            'serial_number' => 'integer',
            'quantity'      => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }
}
