<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLocationStockThreshold extends Model
{
    protected $fillable = ['product_id', 'location_id', 'minimum_quantity'];
    protected function casts(): array { return ['minimum_quantity' => 'decimal:3']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
}
