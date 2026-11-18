<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockDisposal extends Model
{
    protected $fillable = [
        'reference', 'type', 'status', 'location_id', 'purchase_invoice_id', 'reason_category', 'reason',
        'created_by', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function invoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(StockDisposalItem::class); }
}
