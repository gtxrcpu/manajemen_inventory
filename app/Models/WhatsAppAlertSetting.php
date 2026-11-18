<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAlertSetting extends Model
{
    protected $table = 'whatsapp_alert_settings';

    protected $fillable = ['enabled', 'low_stock_enabled', 'supplier_arrival_enabled', 'transfer_approval_enabled', 'transfer_arrival_enabled', 'transfer_receipt_enabled', 'stock_in_approval_enabled', 'recipients', 'location_recipients', 'warehouse_staff_recipients', 'warehouse_pic_recipients', 'message_template', 'risk_accepted_at'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'low_stock_enabled' => 'boolean', 'supplier_arrival_enabled' => 'boolean', 'transfer_approval_enabled' => 'boolean', 'transfer_arrival_enabled' => 'boolean', 'transfer_receipt_enabled' => 'boolean', 'stock_in_approval_enabled' => 'boolean', 'recipients' => 'array', 'location_recipients' => 'array', 'warehouse_staff_recipients' => 'array', 'warehouse_pic_recipients' => 'array', 'risk_accepted_at' => 'datetime'];
    }
}
