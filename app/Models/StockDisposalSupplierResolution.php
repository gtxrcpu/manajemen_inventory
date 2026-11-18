<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDisposalSupplierResolution extends Model
{
    protected $fillable = [
        'stock_disposal_item_id', 'resolution_type', 'quantity', 'amount', 'reference',
        'replacement_stock_in_id', 'recorded_by', 'resolved_at', 'note',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'amount' => 'decimal:2', 'resolved_at' => 'datetime'];
    }

    public function item(): BelongsTo { return $this->belongsTo(StockDisposalItem::class, 'stock_disposal_item_id'); }
    public function replacementReceipt(): BelongsTo { return $this->belongsTo(StockIn::class, 'replacement_stock_in_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by')->withTrashed(); }
}
