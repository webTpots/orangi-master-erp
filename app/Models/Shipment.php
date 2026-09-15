<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status Constants ─────────────────────────────────────────
    const STATUS_CREATED         = 'created';
    const STATUS_PICKED_UP       = 'picked_up';
    const STATUS_IN_TRANSIT      = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED       = 'delivered';
    const STATUS_RTO_INITIATED   = 'rto_initiated';
    const STATUS_RTO_IN_TRANSIT  = 'rto_in_transit';
    const STATUS_RTO_DELIVERED   = 'rto_delivered';
    const STATUS_LOST            = 'lost';
    const STATUS_DAMAGED         = 'damaged';

    const STATUSES = [
        self::STATUS_CREATED,
        self::STATUS_PICKED_UP,
        self::STATUS_IN_TRANSIT,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_RTO_INITIATED,
        self::STATUS_RTO_IN_TRANSIT,
        self::STATUS_RTO_DELIVERED,
        self::STATUS_LOST,
        self::STATUS_DAMAGED,
    ];

    const STATUS_LABELS = [
        'created'          => 'Created',
        'picked_up'        => 'Picked Up',
        'in_transit'       => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered'        => 'Delivered',
        'rto_initiated'    => 'RTO Initiated',
        'rto_in_transit'   => 'RTO In Transit',
        'rto_delivered'    => 'RTO Delivered',
        'lost'             => 'Lost',
        'damaged'          => 'Damaged',
    ];

    /**
     * Valid state transitions: from_status => [to_statuses].
     */
    const TRANSITIONS = [
        'created'          => ['picked_up', 'lost', 'damaged'],
        'picked_up'        => ['in_transit', 'lost', 'damaged'],
        'in_transit'       => ['out_for_delivery', 'delivered', 'rto_initiated', 'lost', 'damaged'],
        'out_for_delivery' => ['delivered', 'rto_initiated', 'lost', 'damaged'],
        'delivered'        => [],
        'rto_initiated'    => ['rto_in_transit', 'lost', 'damaged'],
        'rto_in_transit'   => ['rto_delivered', 'lost', 'damaged'],
        'rto_delivered'    => [],
        'lost'             => [],
        'damaged'          => [],
    ];

    protected $fillable = [
        'company_id',
        'order_id',
        'sub_order_id',
        'tracking_number',
        'awb_number',
        'courier_name',
        'courier_code',
        'status',
        'shipped_at',
        'delivered_at',
        'rto_delivered_at',
        'weight_grams',
        'dimensions_json',
        'pickup_address_json',
        'delivery_address_json',
        'last_scan_at',
        'last_scan_location',
        'last_scan_status',
        'estimated_delivery_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'            => 'datetime',
            'delivered_at'          => 'datetime',
            'rto_delivered_at'      => 'datetime',
            'last_scan_at'          => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'dimensions_json'       => 'array',
            'pickup_address_json'   => 'array',
            'delivery_address_json' => 'array',
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

    public function scans(): HasMany
    {
        return $this->hasMany(ShipmentScan::class)->orderByDesc('scanned_at');
    }

    public function scanLogs(): MorphMany
    {
        return $this->morphMany(ScanLog::class, 'scannable');
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
     * Change the shipment status.
     *
     * @throws \RuntimeException
     */
    public function changeStatus(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid shipment status transition from '{$this->status}' to '{$newStatus}'."
            );
        }

        $this->status = $newStatus;

        // Set timestamps based on status
        if ($newStatus === self::STATUS_PICKED_UP && ! $this->shipped_at) {
            $this->shipped_at = now();
        }
        if ($newStatus === self::STATUS_DELIVERED && ! $this->delivered_at) {
            $this->delivered_at = now();
        }
        if ($newStatus === self::STATUS_RTO_DELIVERED && ! $this->rto_delivered_at) {
            $this->rto_delivered_at = now();
        }

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

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('tracking_number', 'like', "%{$search}%")
              ->orWhere('awb_number', 'like', "%{$search}%")
              ->orWhere('courier_name', 'like', "%{$search}%")
              ->orWhereHas('order', function ($oq) use ($search) {
                  $oq->where('marketplace_order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
              });
        });
    }
}
