<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferReceipt extends Model
{
    protected $fillable = ['stock_transfer_id', 'stock_in_id', 'received_by', 'received_at', 'is_final', 'notes'];
    protected function casts(): array { return ['received_at' => 'datetime', 'is_final' => 'boolean']; }
    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(StockTransferReceiptItem::class); }
}
