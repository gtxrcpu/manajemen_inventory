<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAlertLog extends Model
{
    protected $table = 'whatsapp_alert_logs';

    protected $fillable = ['low_stock_alert_id', 'recipient', 'status', 'provider_message_id', 'error_message', 'last_attempt_at', 'sent_at'];

    protected function casts(): array
    {
        return ['last_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    protected function friendlyError(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function (): ?string {
            if (! $this->error_message) return null;

            $error = strtolower($this->error_message);
            if (str_contains($error, 'could not be processed') || str_contains($error, 'belum mengonfirmasi') || str_contains($error, 'tanda penerimaan pesan')) {
                return 'WhatsApp belum mengonfirmasi pesan. Sistem akan mencoba lagi.';
            }
            if (str_contains($error, 'not connected') || str_contains($error, 'belum tertaut')) {
                return 'WhatsApp sedang tidak terhubung. Pastikan perangkat tertaut dan layanan WhatsApp berjalan.';
            }
            if (str_contains($error, 'tidak terdaftar di whatsapp')) {
                return 'Nomor penerima belum terdaftar di WhatsApp. Periksa nomor tujuan.';
            }
            if (str_contains($error, 'tidak aktif di pengaturan')) {
                return 'Alert ini tidak dikirim karena nomor tersebut sudah dihapus dari daftar penerima.';
            }
            if (str_contains($error, 'dinonaktifkan sebelum pesan dikirim')) {
                return 'Alert ini tidak dikirim karena pengiriman WhatsApp dinonaktifkan.';
            }

            return 'Pesan belum berhasil dikirim. Sistem akan mencoba lagi.';
        });
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(LowStockAlert::class, 'low_stock_alert_id');
    }
}
