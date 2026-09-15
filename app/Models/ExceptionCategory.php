<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExceptionCategory extends Model
{
    use HasFactory;

    protected $table = 'exception_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_severity',
        'default_assignee_role',
        'sla_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sla_hours'  => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function exceptions(): HasMany
    {
        return $this->hasMany(AppException::class, 'category_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
