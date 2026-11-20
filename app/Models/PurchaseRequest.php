<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'reference', 'requested_by', 'location_id', 'priority', 'justification', 'recommended_supplier_id', 'estimated_total',
        'required_date', 'status', 'reviewed_by', 'approved_by', 'rejected_by', 'reviewed_at', 'review_note', 'notes',
    ];

    protected function casts(): array { return ['required_date' => 'date', 'reviewed_at' => 'datetime', 'estimated_total' => 'decimal:2']; }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function recommendedSupplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'recommended_supplier_id')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(PurchaseRequestItem::class); }
    public function purchaseOrder(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}
