<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    protected $fillable = ['stock_in_id', 'purchase_order_item_id', 'product_id', 'quantity', 'delivered_quantity', 'invoice_quantity', 'variance_note', 'batch_number', 'expiry_date', 'batch_received_at'];

    protected function casts(): array { return ['quantity' => 'decimal:3', 'delivered_quantity' => 'decimal:3', 'invoice_quantity' => 'decimal:3', 'expiry_date' => 'date', 'batch_received_at' => 'date']; }

    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function purchaseOrderItem(): BelongsTo { return $this->belongsTo(PurchaseOrderItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(StockMovement::class, 'batch_id'); }
}
