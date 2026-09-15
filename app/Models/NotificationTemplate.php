<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $table = 'notification_templates';

    const CHANNEL_IN_APP   = 'in_app';
    const CHANNEL_EMAIL    = 'email';
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_SMS      = 'sms';

    const CHANNELS = [
        self::CHANNEL_IN_APP,
        self::CHANNEL_EMAIL,
        self::CHANNEL_WHATSAPP,
        self::CHANNEL_SMS,
    ];

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'channel',
        'subject',
        'body_template',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    // ── Methods ────────────────────────────────────────────────────

    /**
     * Render the body template with given variables.
     */
    public function render(array $variables = []): string
    {
        $body = $this->body_template;

        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return $body;
    }

    /**
     * Render the subject with given variables.
     */
    public function renderSubject(array $variables = []): ?string
    {
        if (! $this->subject) {
            return null;
        }

        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
        }

        return $subject;
    }
}
