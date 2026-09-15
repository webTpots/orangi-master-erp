<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    const TYPE_ORDER_EXCEPTION       = 'order_exception';
    const TYPE_SETTLEMENT_IMPORTED   = 'settlement_imported';
    const TYPE_INVENTORY_ALERT       = 'inventory_alert';
    const TYPE_RETURN_RECEIVED       = 'return_received';
    const TYPE_CLAIM_UPDATE          = 'claim_update';
    const TYPE_EXCEPTION_ASSIGNED    = 'exception_assigned';
    const TYPE_EXCEPTION_ESCALATED   = 'exception_escalated';
    const TYPE_SLA_BREACH            = 'sla_breach';
    const TYPE_SYSTEM                = 'system';

    const TYPES = [
        self::TYPE_ORDER_EXCEPTION,
        self::TYPE_SETTLEMENT_IMPORTED,
        self::TYPE_INVENTORY_ALERT,
        self::TYPE_RETURN_RECEIVED,
        self::TYPE_CLAIM_UPDATE,
        self::TYPE_EXCEPTION_ASSIGNED,
        self::TYPE_EXCEPTION_ESCALATED,
        self::TYPE_SLA_BREACH,
        self::TYPE_SYSTEM,
    ];

    const TYPE_LABELS = [
        'order_exception'     => 'Order Exception',
        'settlement_imported' => 'Settlement Imported',
        'inventory_alert'     => 'Inventory Alert',
        'return_received'     => 'Return Received',
        'claim_update'        => 'Claim Update',
        'exception_assigned'  => 'Exception Assigned',
        'exception_escalated' => 'Exception Escalated',
        'sla_breach'          => 'SLA Breach',
        'system'              => 'System',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'data',
        'entity_type',
        'entity_id',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Methods ────────────────────────────────────────────────────

    public function markAsRead(): self
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this;
    }
}
