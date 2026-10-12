<?php

namespace App\Jobs;

use App\Models\LowStockAlert;
use App\Models\WhatsAppAlertLog;
use App\Models\WhatsAppAlertSetting;
use App\Contracts\NotificationProvider;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendLowStockWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $alertId, public readonly string $recipient) {}

    public function handle(NotificationProvider $provider): void
    {
        $settings = WhatsAppAlertSetting::query()->first();
        $recipientEnabled = config('services.whatsapp.provider_approved')
            && $settings?->enabled
            && $settings->risk_accepted_at
            && in_array($this->recipient, $settings->recipients ?? [], true);

        $log = DB::transaction(function () use ($recipientEnabled) {
            $log = WhatsAppAlertLog::query()
                ->where('low_stock_alert_id', $this->alertId)
                ->where('recipient', $this->recipient)
                ->lockForUpdate()
                ->first();

            if (! $log || $log->status !== 'queued') return null;
            if (! $recipientEnabled) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => 'Nomor penerima sudah tidak aktif di pengaturan WhatsApp.',
                ]);
                return null;
            }

            $log->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return $log;
        });

        if (! $log) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $log->update(['status' => 'failed', 'error_message' => 'WhatsApp sedang tidak terhubung.']);
            return;
        }

        $alert = LowStockAlert::with(['product.unit', 'location'])->findOrFail($this->alertId);
        $template = $settings->message_template ?: "🔔 *Peringatan Stok Minimum*\n\nProduk: *{product}*\nLokasi: {location}\nStok saat ini: *{stock} {unit}*\nBatas minimum: {minimum} {unit}\n\nMohon periksa dan lakukan pengadaan bila diperlukan.";
        $message = strtr($template, [
            '{product}' => $alert->product->name,
            '{sku}' => $alert->product->sku,
            '{location}' => $alert->location->name,
            '{stock}' => QuantityFormatter::format($alert->stock_quantity),
            '{minimum}' => QuantityFormatter::format($alert->minimum_quantity),
            '{unit}' => $alert->product->unit->symbol,
        ]);

        try {
            $messageId = $provider->send($this->recipient, $message);
            $log->update(['status' => 'sent', 'provider_message_id' => $messageId, 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000)]);
        }
    }

    public function failed(Throwable $exception): void
    {
        WhatsAppAlertLog::where('low_stock_alert_id', $this->alertId)->where('recipient', $this->recipient)->update([
            'status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
