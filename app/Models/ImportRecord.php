<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_data',
        'normalized_data',
        'status',
        'error_message',
        'matched_sku_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_data'        => 'array',
            'normalized_data' => 'array',
            'row_number'      => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function matchedSku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'matched_sku_id');
    }
}
