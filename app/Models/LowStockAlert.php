<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LowStockAlert extends Model
{
    protected $fillable = ['product_id', 'location_id', 'minimum_quantity', 'stock_quantity', 'triggered_at', 'resolved_at'];
    protected function casts(): array { return ['minimum_quantity' => 'decimal:3', 'stock_quantity' => 'decimal:3', 'triggered_at' => 'datetime', 'resolved_at' => 'datetime']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function scopeActive(Builder $query): Builder { return $query->whereNull('resolved_at'); }
}
