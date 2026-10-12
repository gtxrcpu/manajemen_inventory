<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAlertSetting extends Model
{
    protected $table = 'whatsapp_alert_settings';

    protected $fillable = ['enabled', 'recipients', 'message_template', 'risk_accepted_at'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'recipients' => 'array', 'risk_accepted_at' => 'datetime'];
    }
}
