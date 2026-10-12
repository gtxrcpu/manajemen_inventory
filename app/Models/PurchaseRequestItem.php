<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    protected $fillable = ['product_id', 'quantity'];
    protected function casts(): array { return ['quantity' => 'decimal:3']; }
    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
}
