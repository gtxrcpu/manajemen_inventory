<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOut extends Model
{
    protected $fillable = ['reference', 'destination', 'purpose', 'location_id', 'issued_by', 'issued_at', 'notes'];

    protected function casts(): array { return ['issued_at' => 'date']; }

    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function items(): HasMany { return $this->hasMany(StockOutItem::class); }
}
