<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'returns';

    // ── Return Types ──────────────────────────────────────────────
    const TYPE_CUSTOMER_RETURN = 'customer_return';
    const TYPE_RTO             = 'rto';
    const TYPE_EXCHANGE        = 'exchange';
    const TYPE_REPLACEMENT     = 'replacement';

    const TYPES = [
        self::TYPE_CUSTOMER_RETURN,
        self::TYPE_RTO,
        self::TYPE_EXCHANGE,
        self::TYPE_REPLACEMENT,
    ];

    const TYPE_LABELS = [
        'customer_return' => 'Customer Return',
        'rto'             => 'RTO',
        'exchange'        => 'Exchange',
        'replacement'     => 'Replacement',
    ];

    // ── Reason Categories ─────────────────────────────────────────
    const REASON_CUSTOMER_INITIATED = 'customer_initiated';
    const REASON_DELIVERY_FAILED    = 'delivery_failed';
    const REASON_WRONG_ITEM         = 'wrong_item';
    const REASON_DAMAGED            = 'damaged';
    const REASON_QUALITY_ISSUE      = 'quality_issue';
    const REASON_NOT_AS_DESCRIBED   = 'not_as_described';
    const REASON_SIZE_ISSUE         = 'size_issue';
    const REASON_OTHER              = 'other';

    const REASON_CATEGORIES = [
        self::REASON_CUSTOMER_INITIATED,
        self::REASON_DELIVERY_FAILED,
        self::REASON_WRONG_ITEM,
        self::REASON_DAMAGED,
        self::REASON_QUALITY_ISSUE,
        self::REASON_NOT_AS_DESCRIBED,
        self::REASON_SIZE_ISSUE,
        self::REASON_OTHER,
    ];

    const REASON_LABELS = [
        'customer_initiated' => 'Customer Initiated',
        'delivery_failed'    => 'Delivery Failed',
        'wrong_item'         => 'Wrong Item',
        'damaged'            => 'Damaged',
        'quality_issue'      => 'Quality Issue',
        'not_as_described'   => 'Not as Described',
        'size_issue'         => 'Size Issue',
        'other'              => 'Other',
    ];

    // ── Status Constants ──────────────────────────────────────────
    const STATUS_INITIATED           = 'initiated';
    const STATUS_IN_TRANSIT          = 'in_transit';
    const STATUS_RECEIVED            = 'received';
    const STATUS_INSPECTING          = 'inspecting';
    const STATUS_INSPECTION_COMPLETE = 'inspection_complete';
    const STATUS_RESTOCKED           = 'restocked';
    const STATUS_REJECTED            = 'rejected';
    const STATUS_DISPOSED            = 'disposed';
    const STATUS_CLAIM_FILED         = 'claim_filed';
    const STATUS_CLAIM_SETTLED       = 'claim_settled';
    const STATUS_CLOSED              = 'closed';

    const STATUSES = [
        self::STATUS_INITIATED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_RECEIVED,
        self::STATUS_INSPECTING,
        self::STATUS_INSPECTION_COMPLETE,
        self::STATUS_RESTOCKED,
        self::STATUS_REJECTED,
        self::STATUS_DISPOSED,
        self::STATUS_CLAIM_FILED,
        self::STATUS_CLAIM_SETTLED,
        self::STATUS_CLOSED,
    ];

    const STATUS_LABELS = [
        'initiated'           => 'Initiated',
        'in_transit'          => 'In Transit',
        'received'            => 'Received',
        'inspecting'          => 'Inspecting',
        'inspection_complete' => 'Inspection Complete',
        'restocked'           => 'Restocked',
        'rejected'            => 'Rejected',
        'disposed'            => 'Disposed',
        'claim_filed'         => 'Claim Filed',
        'claim_settled'       => 'Claim Settled',
        'closed'              => 'Closed',
    ];

    /**
     * Valid state transitions: from_status => [to_statuses].
     */
    const TRANSITIONS = [
        'initiated'           => ['in_transit', 'received', 'closed'],
        'in_transit'          => ['received', 'closed'],
        'received'            => ['inspecting'],
        'inspecting'          => ['inspection_complete'],
        'inspection_complete' => ['restocked', 'rejected', 'disposed', 'claim_filed'],
        'restocked'           => ['closed'],
        'rejected'            => ['disposed', 'claim_filed', 'closed'],
        'disposed'            => ['claim_filed', 'closed'],
        'claim_filed'         => ['claim_settled', 'closed'],
        'claim_settled'       => ['closed'],
        'closed'              => [],
    ];

    protected $fillable = [
        'company_id',
        'order_id',
        'sub_order_id',
        'return_type',
        'reason_category',
        'reason_detail',
        'status',
        'marketplace_return_id',
        'tracking_number',
        'courier_name',
        'initiated_at',
        'received_at',
        'inspected_at',
        'closed_at',
        'return_address_json',
    ];

    protected function casts(): array
    {
        return [
            'initiated_at'       => 'datetime',
            'received_at'        => 'datetime',
            'inspected_at'       => 'datetime',
            'closed_at'          => 'datetime',
            'return_address_json' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(ReturnInspection::class, 'return_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'return_id');
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
        });
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::TYPE_LABELS[$this->return_type] ?? ucfirst($this->return_type);
        });
    }

    protected function reasonLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::REASON_LABELS[$this->reason_category] ?? ucfirst($this->reason_category);
        });
    }

    // ── State Machine ──────────────────────────────────────────────

    /**
     * Check if a status transition is allowed.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    /**
     * Change the return status.
     *
     * @throws \RuntimeException
     */
    public function changeStatus(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid status transition from '{$this->status}' to '{$newStatus}'."
            );
        }

        $this->status = $newStatus;
        $this->save();
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
