<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = ['reference', 'po_number', 'purchase_request_id', 'supplier_id', 'location_id', 'destination_location_id', 'created_by', 'order_date', 'expected_delivery', 'expected_delivery_date', 'payment_terms', 'delivery_terms', 'subtotal', 'tax_rate', 'tax_amount', 'shipping_cost', 'other_cost', 'grand_total', 'status', 'notes', 'received_at', 'supplier_follow_up_action', 'supplier_follow_up_due_at', 'supplier_follow_up_note', 'supplier_follow_up_by', 'supplier_follow_up_at'];
    protected function casts(): array { return ['order_date' => 'date', 'expected_delivery' => 'date', 'expected_delivery_date' => 'date', 'subtotal' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'other_cost' => 'decimal:2', 'grand_total' => 'decimal:2', 'received_at' => 'datetime', 'supplier_follow_up_due_at' => 'date', 'supplier_follow_up_at' => 'datetime']; }
    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class)->withTrashed(); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function destinationLocation(): BelongsTo { return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function stockIn(): HasMany { return $this->hasMany(StockIn::class); }
    public function invoices(): HasMany { return $this->hasMany(PurchaseInvoice::class, 'purchase_order_id'); }
    public function supplierFollowUpBy(): BelongsTo { return $this->belongsTo(User::class, 'supplier_follow_up_by')->withTrashed(); }
}
