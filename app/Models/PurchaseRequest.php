<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $fillable = ['reference', 'requested_by', 'required_date', 'status', 'reviewed_by', 'reviewed_at', 'review_note', 'notes'];

    protected function casts(): array { return ['required_date' => 'date', 'reviewed_at' => 'datetime']; }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseRequestItem::class); }
    public function purchaseOrder(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}
