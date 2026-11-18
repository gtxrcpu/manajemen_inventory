<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDisposalItem extends Model
{
    protected $fillable = ['stock_disposal_id', 'product_id', 'batch_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function disposal(): BelongsTo { return $this->belongsTo(StockDisposal::class, 'stock_disposal_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function batch(): BelongsTo { return $this->belongsTo(StockInItem::class, 'batch_id'); }
    public function supplierResolutions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(StockDisposalSupplierResolution::class); }
}
