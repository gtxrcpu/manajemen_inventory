<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    protected $fillable = ['reference', 'idempotency_key', 'supplier_id', 'purchase_order_id', 'purchase_invoice_id', 'source', 'location_id', 'received_by', 'received_at', 'notes', 'status', 'decision_by', 'decided_at', 'rejection_reason', 'rejection_category'];

    protected function casts(): array { return ['received_at' => 'date', 'decided_at' => 'datetime']; }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function purchaseInvoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function decisionMaker(): BelongsTo { return $this->belongsTo(User::class, 'decision_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(StockInItem::class); }
    public function evidences(): HasMany { return $this->hasMany(StockInEvidence::class); }
}
