<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['product_id', 'description', 'quantity', 'qty_ordered', 'qty_received', 'unit_price', 'discount_amount', 'subtotal'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'qty_ordered' => 'decimal:3', 'qty_received' => 'decimal:3', 'unit_price' => 'decimal:2', 'discount_amount' => 'decimal:2', 'subtotal' => 'decimal:2']; }
    public function order(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
}
