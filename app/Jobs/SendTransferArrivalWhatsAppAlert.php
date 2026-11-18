<?php

namespace App\Jobs;

use App\Contracts\NotificationProvider;
use App\Models\StockTransfer;
use App\Models\StockTransferArrivalWhatsAppLog;
use App\Services\TransferArrivalAlertService;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendTransferArrivalWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $logId) {}

    public function backoff(): array { return [30, 120]; }

    public function handle(NotificationProvider $provider, TransferArrivalAlertService $alerts): void
    {
        $log = StockTransferArrivalWhatsAppLog::with('transfer')->findOrFail($this->logId);
        if (! $alerts->isRecipientForTransfer($log->transfer, $log->recipient)) {
            $log->update(['status' => 'failed', 'error_message' => 'Alert ETA dinonaktifkan atau nomor bukan Staff Outlet aktif di lokasi tujuan.']);
            return;
        }

        $claimed = DB::transaction(function () {
            $current = StockTransferArrivalWhatsAppLog::query()->whereKey($this->logId)->lockForUpdate()->first();
            if (! $current || $current->status !== 'queued') return false;
            $current->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return true;
        });
        if (! $claimed) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $this->requeue('WhatsApp sedang tidak terhubung.');
            throw new RuntimeException('WhatsApp sedang tidak terhubung. Notifikasi estimasi transfer akan dicoba kembali.');
        }

        $transfer = StockTransfer::with(['sourceLocation', 'destinationLocation', 'items.product.unit'])->findOrFail($log->stock_transfer_id);
        $items = $transfer->items->map(fn ($item) => '• '.$item->product->name.': '.QuantityFormatter::format($item->quantity).' '.($item->product->unit->symbol ?? 'unit'))->implode("\n");
        $eta = $transfer->expected_arrival_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB';
        $message = "📦 *INFORMASI PENGIRIMAN STOK*\n_Sistem Inventaris Technolife_\n\n"
            ."Transfer: *{$transfer->reference}*\nDari: *{$transfer->sourceLocation->name}*\nTujuan: *{$transfer->destinationLocation->name}*\nEstimasi tiba: *{$eta}*\n\n*Barang yang dikirim:*\n{$items}\n\nMohon siapkan penerimaan dan catat jumlah aktual saat barang tiba.";

        try {
            $messageId = $provider->send($log->recipient, $message);
            $log->update(['status' => 'sent', 'provider_message_id' => $messageId, 'sent_at' => now(), 'error_message' => null]);
        } catch (Throwable $exception) {
            $this->requeue($exception->getMessage());
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        StockTransferArrivalWhatsAppLog::whereKey($this->logId)->update([
            'status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    private function requeue(string $message): void
    {
        StockTransferArrivalWhatsAppLog::whereKey($this->logId)->update([
            'status' => $this->attempts() >= $this->tries ? 'failed' : 'queued',
            'error_message' => mb_substr($message, 0, 2000),
        ]);
    }
}
