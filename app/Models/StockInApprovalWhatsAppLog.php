<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInApprovalWhatsAppLog extends Model
{
    protected $table = 'stock_in_approval_whatsapp_logs';

    protected $fillable = ['stock_in_id', 'recipient', 'status', 'provider_message_id', 'error_message', 'last_attempt_at', 'sent_at'];

    protected function casts(): array
    {
        return ['last_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class, 'stock_in_id');
    }
}
