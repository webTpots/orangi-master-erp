<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'company_id',
        'warehouse_id',
        'receipt_number',
        'receipt_date',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
        ];
    }

    // ── Boot ─────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $gr) {
            if (empty($gr->receipt_number)) {
                $gr->receipt_number = static::generateReceiptNumber();
            }
        });
    }

    public static function generateReceiptNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "GR-{$year}-";

        $lastGr = static::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        if ($lastGr) {
            $lastSeq = (int) substr($lastGr, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }
}
