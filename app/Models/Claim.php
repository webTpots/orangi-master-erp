<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Claim extends Model
{
    use HasFactory, SoftDeletes;

    // ── Claim Types ───────────────────────────────────────────────
    const TYPE_RTO_SHIPPING       = 'rto_shipping';
    const TYPE_DAMAGED_IN_TRANSIT = 'damaged_in_transit';
    const TYPE_LOST_IN_TRANSIT    = 'lost_in_transit';
    const TYPE_WRONG_DELIVERY     = 'wrong_delivery';
    const TYPE_MARKETPLACE_PENALTY = 'marketplace_penalty';
    const TYPE_WEIGHT_DISCREPANCY = 'weight_discrepancy';
    const TYPE_OTHER              = 'other';

    const TYPES = [
        self::TYPE_RTO_SHIPPING,
        self::TYPE_DAMAGED_IN_TRANSIT,
        self::TYPE_LOST_IN_TRANSIT,
        self::TYPE_WRONG_DELIVERY,
        self::TYPE_MARKETPLACE_PENALTY,
        self::TYPE_WEIGHT_DISCREPANCY,
        self::TYPE_OTHER,
    ];

    const TYPE_LABELS = [
        'rto_shipping'       => 'RTO Shipping',
        'damaged_in_transit' => 'Damaged in Transit',
        'lost_in_transit'    => 'Lost in Transit',
        'wrong_delivery'     => 'Wrong Delivery',
        'marketplace_penalty' => 'Marketplace Penalty',
        'weight_discrepancy' => 'Weight Discrepancy',
        'other'              => 'Other',
    ];

    // ── Claim Against ─────────────────────────────────────────────
    const AGAINST_COURIER    = 'courier';
    const AGAINST_MARKETPLACE = 'marketplace';
    const AGAINST_CUSTOMER   = 'customer';
    const AGAINST_INSURANCE  = 'insurance';

    const AGAINST_OPTIONS = [
        self::AGAINST_COURIER,
        self::AGAINST_MARKETPLACE,
        self::AGAINST_CUSTOMER,
        self::AGAINST_INSURANCE,
    ];

    const AGAINST_LABELS = [
        'courier'     => 'Courier',
        'marketplace' => 'Marketplace',
        'customer'    => 'Customer',
        'insurance'   => 'Insurance',
    ];

    // ── Status Constants ──────────────────────────────────────────
    const STATUS_DRAFT              = 'draft';
    const STATUS_FILED              = 'filed';
    const STATUS_UNDER_REVIEW       = 'under_review';
    const STATUS_APPROVED           = 'approved';
    const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    const STATUS_REJECTED           = 'rejected';
    const STATUS_SETTLED            = 'settled';
    const STATUS_CLOSED             = 'closed';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_FILED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_PARTIALLY_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SETTLED,
        self::STATUS_CLOSED,
    ];

    const STATUS_LABELS = [
        'draft'              => 'Draft',
        'filed'              => 'Filed',
        'under_review'       => 'Under Review',
        'approved'           => 'Approved',
        'partially_approved' => 'Partially Approved',
        'rejected'           => 'Rejected',
        'settled'            => 'Settled',
        'closed'             => 'Closed',
    ];

    /**
     * Valid state transitions: from_status => [to_statuses].
     */
    const TRANSITIONS = [
        'draft'              => ['filed'],
        'filed'              => ['under_review', 'rejected'],
        'under_review'       => ['approved', 'partially_approved', 'rejected'],
        'approved'           => ['settled', 'closed'],
        'partially_approved' => ['settled', 'closed'],
        'rejected'           => ['closed'],
        'settled'            => ['closed'],
        'closed'             => [],
    ];

    protected $fillable = [
        'company_id',
        'return_id',
        'order_id',
        'claim_type',
        'claim_against',
        'status',
        'reference_number',
        'claimed_amount',
        'approved_amount',
        'settled_amount',
        'currency',
        'filed_at',
        'resolved_at',
        'evidence_json',
        'notes',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount'  => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'settled_amount'  => 'decimal:2',
            'filed_at'        => 'datetime',
            'resolved_at'     => 'datetime',
            'evidence_json'   => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class, 'return_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ClaimCommunication::class)->orderByDesc('communicated_at');
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
            return self::TYPE_LABELS[$this->claim_type] ?? ucfirst($this->claim_type);
        });
    }

    protected function againstLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::AGAINST_LABELS[$this->claim_against] ?? ucfirst($this->claim_against);
        });
    }

    // ── State Machine ──────────────────────────────────────────────

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function changeStatus(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid claim status transition from '{$this->status}' to '{$newStatus}'."
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
