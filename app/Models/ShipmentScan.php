<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentScan extends Model
{
    use HasFactory;

    /**
     * Immutable: no updated_at column.
     */
    const UPDATED_AT = null;

    // ── Scan Type Constants ──────────────────────────────────────
    const TYPE_PICKUP        = 'pickup';
    const TYPE_IN_TRANSIT    = 'in_transit';
    const TYPE_HUB           = 'hub';
    const TYPE_OUT_FOR_DELIVERY = 'out_for_delivery';
    const TYPE_DELIVERED     = 'delivered';
    const TYPE_RTO_INITIATED = 'rto_initiated';
    const TYPE_RTO_PICKUP    = 'rto_pickup';
    const TYPE_RTO_IN_TRANSIT = 'rto_in_transit';
    const TYPE_RTO_DELIVERED = 'rto_delivered';
    const TYPE_EXCEPTION     = 'exception';
    const TYPE_DAMAGED       = 'damaged';
    const TYPE_LOST          = 'lost';

    const SCAN_TYPES = [
        self::TYPE_PICKUP,
        self::TYPE_IN_TRANSIT,
        self::TYPE_HUB,
        self::TYPE_OUT_FOR_DELIVERY,
        self::TYPE_DELIVERED,
        self::TYPE_RTO_INITIATED,
        self::TYPE_RTO_PICKUP,
        self::TYPE_RTO_IN_TRANSIT,
        self::TYPE_RTO_DELIVERED,
        self::TYPE_EXCEPTION,
        self::TYPE_DAMAGED,
        self::TYPE_LOST,
    ];

    const SCAN_TYPE_LABELS = [
        'pickup'           => 'Pickup',
        'in_transit'       => 'In Transit',
        'hub'              => 'Hub',
        'out_for_delivery' => 'Out for Delivery',
        'delivered'        => 'Delivered',
        'rto_initiated'    => 'RTO Initiated',
        'rto_pickup'       => 'RTO Pickup',
        'rto_in_transit'   => 'RTO In Transit',
        'rto_delivered'    => 'RTO Delivered',
        'exception'        => 'Exception',
        'damaged'          => 'Damaged',
        'lost'             => 'Lost',
    ];

    protected $fillable = [
        'shipment_id',
        'scan_type',
        'scanned_at',
        'location',
        'city',
        'state',
        'status_code',
        'status_description',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'raw_data'   => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getScanTypeLabelAttribute(): string
    {
        return self::SCAN_TYPE_LABELS[$this->scan_type] ?? ucfirst($this->scan_type);
    }
}
