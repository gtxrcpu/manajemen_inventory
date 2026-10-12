<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $fillable = ['reference', 'product_id', 'location_id', 'created_by', 'direction', 'quantity', 'old_quantity', 'new_quantity', 'reason'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'old_quantity' => 'decimal:3', 'new_quantity' => 'decimal:3']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
