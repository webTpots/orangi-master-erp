<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'order_id',
        'sub_order_id',
        'marketplace_id',
        'label_file_id',
        'awb_number',
        'courier_partner',
        'tracking_number',
        'customer_name',
        'customer_city',
        'customer_state',
        'customer_pincode',
        'payment_type',
        'sku',
        'size',
        'quantity',
        'color',
        'sub_order_number',
        'invoice_number',
        'invoice_date',
        'invoice_amount',
        'taxable_value',
        'tax_amount',
        'raw_text',
        'page_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'   => 'date',
            'invoice_amount' => 'decimal:2',
            'taxable_value'  => 'decimal:2',
            'tax_amount'     => 'decimal:2',
            'quantity'       => 'integer',
            'page_number'    => 'integer',
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

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function labelFile(): BelongsTo
    {
        return $this->belongsTo(LabelFile::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLinked($query)
    {
        return $query->where('status', 'linked');
    }

    public function scopeUnlinked($query)
    {
        return $query->where('status', 'parsed');
    }

    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }
}
