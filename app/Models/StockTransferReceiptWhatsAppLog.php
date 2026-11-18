<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferReceiptWhatsAppLog extends Model
{
    protected $table = 'stock_transfer_receipt_whatsapp_logs';

    protected $fillable = ['stock_transfer_receipt_id', 'recipient', 'status', 'provider_message_id', 'error_message', 'last_attempt_at', 'sent_at'];

    protected function casts(): array { return ['last_attempt_at' => 'datetime', 'sent_at' => 'datetime']; }

    public function receipt(): BelongsTo { return $this->belongsTo(StockTransferReceipt::class, 'stock_transfer_receipt_id'); }
}
