<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnInspection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'return_id',
        'inspected_by',
        'condition',
        'is_resellable',
        'inspection_notes',
        'photos_json',
        'inspected_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_resellable' => 'boolean',
            'photos_json'   => 'array',
            'inspected_at'  => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    // ── Condition Constants ───────────────────────────────────────

    const CONDITION_LIKE_NEW     = 'like_new';
    const CONDITION_GOOD         = 'good';
    const CONDITION_MINOR_DAMAGE = 'minor_damage';
    const CONDITION_MAJOR_DAMAGE = 'major_damage';
    const CONDITION_UNSELLABLE   = 'unsellable';
    const CONDITION_MISSING      = 'missing';

    const CONDITIONS = [
        self::CONDITION_LIKE_NEW,
        self::CONDITION_GOOD,
        self::CONDITION_MINOR_DAMAGE,
        self::CONDITION_MAJOR_DAMAGE,
        self::CONDITION_UNSELLABLE,
        self::CONDITION_MISSING,
    ];

    const CONDITION_LABELS = [
        'like_new'     => 'Like New',
        'good'         => 'Good',
        'minor_damage' => 'Minor Damage',
        'major_damage' => 'Major Damage',
        'unsellable'   => 'Unsellable',
        'missing'      => 'Missing',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class, 'return_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
