<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferRequest extends Model
{
    protected $fillable = ['reference', 'location_id', 'requested_by', 'picked_by', 'stock_transfer_id', 'status', 'requested_at', 'picked_at', 'notes'];

    protected function casts(): array { return ['requested_at' => 'datetime', 'picked_at' => 'datetime']; }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function picker(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function items(): HasMany { return $this->hasMany(StockTransferRequestItem::class); }
}
