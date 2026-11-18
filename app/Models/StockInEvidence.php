<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInEvidence extends Model
{
    protected $table = 'stock_in_evidences';

    protected $fillable = ['stock_in_id', 'file_path', 'original_name', 'mime_type', 'size_bytes'];

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }
}
