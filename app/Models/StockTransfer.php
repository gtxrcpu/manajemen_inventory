<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference', 'source_location_id', 'destination_location_id', 'stock_out_id', 'stock_in_id',
        'stock_transfer_request_id', 'prepared_by', 'approved_by', 'approved_at', 'dispatched_by', 'received_by', 'status', 'dispatched_at', 'expected_arrival_at', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'dispatched_at' => 'datetime', 'expected_arrival_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function sourceLocation(): BelongsTo { return $this->belongsTo(Location::class, 'source_location_id')->withTrashed(); }
    public function destinationLocation(): BelongsTo { return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed(); }
    public function dispatcher(): BelongsTo { return $this->belongsTo(User::class, 'dispatched_by')->withTrashed(); }
    public function preparedBy(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by')->withTrashed(); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by')->withTrashed(); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by')->withTrashed(); }
    public function stockOut(): BelongsTo { return $this->belongsTo(StockOut::class); }
    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function items(): HasMany { return $this->hasMany(StockTransferItem::class); }
    public function request(): BelongsTo { return $this->belongsTo(StockTransferRequest::class, 'stock_transfer_request_id'); }
    public function receipts(): HasMany { return $this->hasMany(StockTransferReceipt::class); }
    public function arrivalWhatsAppLogs(): HasMany { return $this->hasMany(StockTransferArrivalWhatsAppLog::class, 'stock_transfer_id'); }
}
