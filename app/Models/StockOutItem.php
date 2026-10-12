<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOutItem extends Model
{
    protected $fillable = ['stock_out_id', 'product_id', 'batch_id', 'quantity'];

    protected function casts(): array { return ['quantity' => 'decimal:3']; }

    public function stockOut(): BelongsTo { return $this->belongsTo(StockOut::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function batch(): BelongsTo { return $this->belongsTo(StockInItem::class, 'batch_id'); }
}
