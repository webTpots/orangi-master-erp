<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'notification_type',
        'channel_in_app',
        'channel_email',
        'channel_whatsapp',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'channel_in_app'    => 'boolean',
            'channel_email'     => 'boolean',
            'channel_whatsapp'  => 'boolean',
            'is_enabled'        => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
