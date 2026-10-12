<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    protected $fillable = ['stock_in_id', 'product_id', 'quantity', 'batch_number', 'expiry_date', 'batch_received_at'];

    protected function casts(): array { return ['quantity' => 'decimal:3', 'expiry_date' => 'date', 'batch_received_at' => 'date']; }

    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(StockMovement::class, 'batch_id'); }
}
