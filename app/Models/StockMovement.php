<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StockMovement extends Model
{
    protected $fillable = ['product_id', 'location_id', 'user_id', 'batch_id', 'stock_in_item_id', 'stock_out_item_id', 'stock_opname_item_id', 'stock_adjustment_id', 'stock_disposal_item_id', 'type', 'quantity', 'note'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class)->withTrashed(); }
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function batch(): BelongsTo { return $this->belongsTo(StockInItem::class, 'batch_id'); }
    public function stockInItem(): BelongsTo { return $this->belongsTo(StockInItem::class); }
    public function stockOutItem(): BelongsTo { return $this->belongsTo(StockOutItem::class); }
    public function stockOpnameItem(): BelongsTo { return $this->belongsTo(StockOpnameItem::class); }
    public function stockAdjustment(): BelongsTo { return $this->belongsTo(StockAdjustment::class); }
    public function stockDisposalItem(): BelongsTo { return $this->belongsTo(StockDisposalItem::class); }

    protected function transactionType(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->stockInItem !== null => 'Stock In',
            $this->stockOutItem !== null => 'Stock Out',
            $this->stockOpnameItem !== null => 'Stock Opname',
            $this->stockAdjustment !== null => 'Adjustment',
            $this->stockDisposalItem !== null => 'Write-off / retur',
            default => $this->type === 'in' ? 'Stock In' : 'Stock Out',
        });
    }

    protected function transactionReference(): Attribute
    {
        return Attribute::get(fn () => $this->stockInItem?->stockIn?->reference
            ?? $this->stockOutItem?->stockOut?->reference
            ?? $this->stockOpnameItem?->opname?->reference
            ?? $this->stockAdjustment?->reference
            ?? $this->stockDisposalItem?->disposal?->reference
            ?? '—');
    }
}
