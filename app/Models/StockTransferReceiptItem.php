<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferReceiptItem extends Model
{
    protected $fillable = ['stock_transfer_receipt_id', 'stock_transfer_item_id', 'stock_in_item_id', 'quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:3']; }
    public function receipt(): BelongsTo { return $this->belongsTo(StockTransferReceipt::class, 'stock_transfer_receipt_id'); }
    public function transferItem(): BelongsTo { return $this->belongsTo(StockTransferItem::class, 'stock_transfer_item_id'); }
    public function stockInItem(): BelongsTo { return $this->belongsTo(StockInItem::class); }
}
