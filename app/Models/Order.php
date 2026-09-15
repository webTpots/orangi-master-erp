<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    use HasFactory;

    // ── Status Constants ─────────────────────────────────────────
    const STATUS_NEW            = 'new';
    const STATUS_ACCEPTED       = 'accepted';
    const STATUS_LABEL_READY    = 'label_ready';
    const STATUS_PICKING        = 'picking';
    const STATUS_PACKED         = 'packed';
    const STATUS_READY_TO_SHIP  = 'ready_to_ship';
    const STATUS_SCANNED        = 'scanned';
    const STATUS_HANDED_OVER    = 'handed_over';
    const STATUS_IN_TRANSIT     = 'in_transit';
    const STATUS_DELIVERED      = 'delivered';
    const STATUS_CANCELLED      = 'cancelled';
    const STATUS_RETURN         = 'return';
    const STATUS_RTO            = 'rto';

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_ACCEPTED,
        self::STATUS_LABEL_READY,
        self::STATUS_PICKING,
        self::STATUS_PACKED,
        self::STATUS_READY_TO_SHIP,
        self::STATUS_SCANNED,
        self::STATUS_HANDED_OVER,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_RETURN,
        self::STATUS_RTO,
    ];

    const STATUS_LABELS = [
        'new'            => 'New',
        'accepted'       => 'Accepted',
        'label_ready'    => 'Label Ready',
        'picking'        => 'Picking',
        'packed'         => 'Packed',
        'ready_to_ship'  => 'Ready to Ship',
        'scanned'        => 'Scanned',
        'handed_over'    => 'Handed Over',
        'in_transit'     => 'In Transit',
        'delivered'      => 'Delivered',
        'cancelled'      => 'Cancelled',
        'return'         => 'Return',
        'rto'            => 'RTO',
    ];

    /**
     * Valid state transitions: from_status => [to_statuses].
     */
    const TRANSITIONS = [
        'new'            => ['accepted', 'cancelled'],
        'accepted'       => ['label_ready', 'cancelled'],
        'label_ready'    => ['picking', 'cancelled'],
        'picking'        => ['packed'],
        'packed'         => ['ready_to_ship', 'scanned'],
        'ready_to_ship'  => ['scanned'],
        'scanned'        => ['handed_over'],
        'handed_over'    => ['in_transit', 'cancelled'],
        'in_transit'     => ['delivered', 'rto'],
        'delivered'      => ['return'],
        'return'         => [],
        'rto'            => [],
        'cancelled'      => [],
    ];

    protected $fillable = [
        'company_id',
        'marketplace_id',
        'marketplace_account_id',
        'marketplace_order_id',
        'order_date',
        'order_time',
        'business_cycle_date',
        'cutoff_time',
        'status',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_state',
        'customer_pincode',
        'payment_type',
        'total_amount',
        'invoice_number',
        'invoice_date',
        'invoice_amount',
        'hsn_code',
        'taxable_value',
        'sgst',
        'cgst',
        'igst',
        'other_charges',
        'courier_partner',
        'awb_number',
        'tracking_url',
        'fulfillment_status',
        'sla_date',
        'is_sla_risk',
        'notes',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'order_date'          => 'date',
            'business_cycle_date' => 'date',
            'invoice_date'        => 'date',
            'sla_date'            => 'date',
            'total_amount'        => 'decimal:2',
            'invoice_amount'      => 'decimal:2',
            'taxable_value'       => 'decimal:2',
            'sgst'                => 'decimal:2',
            'cgst'                => 'decimal:2',
            'igst'                => 'decimal:2',
            'other_charges'       => 'decimal:2',
            'is_sla_risk'         => 'boolean',
            'raw_data'            => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('changed_at');
    }

    // ── Accessors ──────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
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
     * Change the order status with audit trail.
     *
     * @throws \RuntimeException
     */
    public function changeStatus(string $newStatus, ?string $notes = null, ?int $userId = null): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid status transition from '{$this->status}' to '{$newStatus}'."
            );
        }

        $fromStatus = $this->status;

        $this->status = $newStatus;
        $this->save();

        OrderStatusHistory::create([
            'order_id'   => $this->id,
            'from_status' => $fromStatus,
            'to_status'   => $newStatus,
            'notes'       => $notes,
            'changed_by'  => $userId,
            'changed_at'  => now(),
        ]);
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

    public function scopeSlaRisk($query)
    {
        return $query->where('is_sla_risk', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('marketplace_order_id', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('awb_number', 'like', "%{$search}%")
              ->orWhere('invoice_number', 'like', "%{$search}%")
              ->orWhereHas('subOrders', function ($sq) use ($search) {
                  $sq->where('sub_order_number', 'like', "%{$search}%")
                    ->orWhere('marketplace_sku', 'like', "%{$search}%");
              });
        });
    }
}
