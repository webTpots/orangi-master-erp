<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'status',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date'             => 'date',
            'expected_delivery_date' => 'date',
            'total_amount'           => 'decimal:2',
            'approved_at'            => 'datetime',
            'cancelled_at'           => 'datetime',
        ];
    }

    // ── Boot ─────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generatePoNumber($po->company_id);
            }
        });
    }

    public static function generatePoNumber(int $companyId): string
    {
        $year = now()->format('Y');
        $prefix = "PO-{$year}-";

        $lastPo = static::where('company_id', $companyId)
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')
            ->value('po_number');

        if ($lastPo) {
            $lastSeq = (int) substr($lastPo, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Status Helpers ──────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'partially_confirmed']);
    }

    public function isReceived(): bool
    {
        return in_array($this->status, ['received', 'partially_received']);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'draft' && $this->lines()->count() > 0;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'confirmed', 'partially_confirmed']);
    }

    public function canReceiveGoods(): bool
    {
        return in_array($this->status, ['sent', 'confirmed', 'partially_confirmed', 'partially_received']);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'closed']);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function recalculateTotal(): self
    {
        $this->total_amount = $this->lines()->sum('line_total');
        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'               => 'neutral',
            'sent'                => 'warning',
            'partially_confirmed' => 'warning',
            'confirmed'           => 'success',
            'partially_received'  => 'brand',
            'received'            => 'success',
            'closed'              => 'neutral',
            'cancelled'           => 'danger',
            default               => 'neutral',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'               => 'Draft',
            'sent'                => 'Sent',
            'partially_confirmed' => 'Partially Confirmed',
            'confirmed'           => 'Confirmed',
            'partially_received'  => 'Partially Received',
            'received'            => 'Received',
            'closed'              => 'Closed',
            'cancelled'           => 'Cancelled',
            default               => ucfirst($this->status),
        };
    }
}
