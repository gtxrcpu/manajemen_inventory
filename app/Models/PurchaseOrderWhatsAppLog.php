<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderWhatsAppLog extends Model
{
    protected $table = 'purchase_order_whatsapp_alert_logs';

    protected $fillable = [
        'purchase_order_id',
        'recipient',
        'status',
        'provider_message_id',
        'error_message',
        'last_attempt_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
