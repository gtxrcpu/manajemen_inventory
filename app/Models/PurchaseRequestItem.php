<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'stock_snapshot', 'estimated_unit_price', 'estimated_subtotal', 'notes'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'stock_snapshot' => 'decimal:3', 'estimated_unit_price' => 'decimal:2', 'estimated_subtotal' => 'decimal:2']; }
    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
}
