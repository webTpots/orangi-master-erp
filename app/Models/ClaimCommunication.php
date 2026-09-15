<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimCommunication extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'claim_id',
        'direction',
        'channel',
        'subject',
        'message',
        'attachments_json',
        'communicated_at',
        'communicated_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments_json' => 'array',
            'communicated_at'  => 'datetime',
            'created_at'       => 'datetime',
        ];
    }

    // ── Constants ─────────────────────────────────────────────────

    const DIRECTION_OUTGOING = 'outgoing';
    const DIRECTION_INCOMING = 'incoming';

    const CHANNEL_EMAIL  = 'email';
    const CHANNEL_PHONE  = 'phone';
    const CHANNEL_PORTAL = 'portal';
    const CHANNEL_CHAT   = 'chat';

    const CHANNEL_LABELS = [
        'email'  => 'Email',
        'phone'  => 'Phone',
        'portal' => 'Portal',
        'chat'   => 'Chat',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function communicator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'communicated_by');
    }
}
