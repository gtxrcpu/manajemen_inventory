<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = ['reference', 'purchase_request_id', 'supplier_id', 'location_id', 'created_by', 'expected_delivery', 'status', 'notes', 'received_at'];
    protected function casts(): array { return ['expected_delivery' => 'date', 'received_at' => 'datetime']; }
    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class)->withTrashed(); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function stockIn(): HasMany { return $this->hasMany(StockIn::class); }
}
