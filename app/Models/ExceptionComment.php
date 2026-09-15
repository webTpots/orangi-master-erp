<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionComment extends Model
{
    use HasFactory;

    protected $table = 'exception_comments';

    public $timestamps = false;

    protected $fillable = [
        'exception_id',
        'user_id',
        'comment',
        'is_internal',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'created_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function exception(): BelongsTo
    {
        return $this->belongsTo(AppException::class, 'exception_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
