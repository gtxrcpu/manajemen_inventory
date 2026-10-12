<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = ['reference', 'period_type', 'status', 'location_id', 'counted_by', 'reviewed_by', 'snapshot_at', 'submitted_at', 'movement_cutoff_id', 'reviewed_at', 'review_note', 'notes'];
    protected function casts(): array { return ['snapshot_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime']; }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function counter(): BelongsTo { return $this->belongsTo(User::class, 'counted_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }
}
