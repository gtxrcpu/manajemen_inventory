<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferDiscrepancyResolution extends Model
{
    protected $fillable = ['stock_transfer_item_id', 'resolution_type', 'quantity', 'reason', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'resolved_at' => 'datetime'];
    }

    public function transferItem(): BelongsTo { return $this->belongsTo(StockTransferItem::class, 'stock_transfer_item_id'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by')->withTrashed(); }
}
