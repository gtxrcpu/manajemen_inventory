<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id', 'product_id', 'source_batch_id', 'destination_batch_id',
        'stock_out_item_id', 'stock_in_item_id', 'quantity', 'received_quantity', 'discrepancy_quantity', 'discrepancy_reason',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'received_quantity' => 'decimal:3', 'discrepancy_quantity' => 'decimal:3'];
    }

    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function sourceBatch(): BelongsTo { return $this->belongsTo(StockInItem::class, 'source_batch_id'); }
    public function destinationBatch(): BelongsTo { return $this->belongsTo(StockInItem::class, 'destination_batch_id'); }
    public function stockOutItem(): BelongsTo { return $this->belongsTo(StockOutItem::class); }
    public function stockInItem(): BelongsTo { return $this->belongsTo(StockInItem::class); }
    public function discrepancyResolutions(): HasMany { return $this->hasMany(StockTransferDiscrepancyResolution::class); }
}
