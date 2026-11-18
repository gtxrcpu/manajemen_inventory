<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'invoice_number', 'invoice_number_key', 'invoice_date', 'document_total',
        'discount_total', 'tax_total', 'shipping_total', 'other_fee_total', 'total_variance_note',
        'document_path', 'document_name', 'status', 'created_by', 'submitted_at', 'reviewed_by',
        'reviewed_at', 'review_note', 'review_category',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date', 'document_total' => 'decimal:2', 'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2', 'shipping_total' => 'decimal:2', 'other_fee_total' => 'decimal:2',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class, 'purchase_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchaseInvoicePayment::class, 'purchase_invoice_id')->orderBy('paid_at')->orderBy('id');
    }

    public function getItemsSubtotalAttribute(): float
    {
        return round((float) $this->items->sum(fn ($item) => round((float) $item->quantity * (float) $item->unit_price, 2)), 2);
    }

    public function getCalculatedTotalAttribute(): float
    {
        return round(
            $this->items_subtotal - (float) $this->discount_total + (float) $this->tax_total
                + (float) $this->shipping_total + (float) $this->other_fee_total,
            2,
        );
    }

    public function getTotalVarianceAttribute(): float
    {
        return round((float) $this->document_total - $this->calculated_total, 2);
    }
}
