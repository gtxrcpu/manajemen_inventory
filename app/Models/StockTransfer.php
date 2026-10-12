<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference', 'source_location_id', 'destination_location_id', 'stock_out_id', 'stock_in_id',
        'stock_transfer_request_id', 'dispatched_by', 'received_by', 'status', 'dispatched_at', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['dispatched_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function sourceLocation(): BelongsTo { return $this->belongsTo(Location::class, 'source_location_id')->withTrashed(); }
    public function destinationLocation(): BelongsTo { return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed(); }
    public function dispatcher(): BelongsTo { return $this->belongsTo(User::class, 'dispatched_by')->withTrashed(); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by')->withTrashed(); }
    public function stockOut(): BelongsTo { return $this->belongsTo(StockOut::class); }
    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function items(): HasMany { return $this->hasMany(StockTransferItem::class); }
    public function request(): BelongsTo { return $this->belongsTo(StockTransferRequest::class, 'stock_transfer_request_id'); }
    public function receipts(): HasMany { return $this->hasMany(StockTransferReceipt::class); }
}
