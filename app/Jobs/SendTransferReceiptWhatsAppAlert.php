<?php

namespace App\Jobs;

use App\Contracts\NotificationProvider;
use App\Models\StockTransferReceiptWhatsAppLog;
use App\Services\TransferReceiptAlertService;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendTransferReceiptWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $logId) {}

    public function backoff(): array { return [30, 120]; }

    public function handle(NotificationProvider $provider, TransferReceiptAlertService $alerts): void
    {
        $log = StockTransferReceiptWhatsAppLog::with('receipt.transfer')->findOrFail($this->logId);
        if (! $alerts->isRecipientForReceipt($log->receipt, $log->recipient)) {
            $log->update(['status' => 'failed', 'error_message' => 'Alert penerimaan dinonaktifkan atau nomor bukan Staff Gudang aktif di lokasi asal.']);
            return;
        }

        $claimed = DB::transaction(function () {
            $current = StockTransferReceiptWhatsAppLog::query()->whereKey($this->logId)->lockForUpdate()->first();
            if (! $current || $current->status !== 'queued') return false;
            $current->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return true;
        });
        if (! $claimed) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $this->requeue('WhatsApp sedang tidak terhubung.');
            throw new RuntimeException('WhatsApp sedang tidak terhubung. Notifikasi penerimaan transfer akan dicoba kembali.');
        }

        $receipt = $log->receipt->load(['transfer.sourceLocation', 'transfer.destinationLocation', 'receiver', 'items.transferItem.product.unit']);
        $transfer = $receipt->transfer;
        $items = $receipt->items->map(fn ($item) => '• '.$item->transferItem->product->name.': '.QuantityFormatter::format($item->quantity).' '.($item->transferItem->product->unit->symbol ?? 'unit'))->implode("\n");
        if ($items === '') $items = 'Tidak ada barang diterima pada pemeriksaan ini.';
        $receivedAt = $receipt->received_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB';
        $message = "✅ *KONFIRMASI PENERIMAAN TRANSFER*\n_Sistem Inventaris Technolife_\n\n"
            ."Transfer: *{$transfer->reference}*\nGudang asal: *{$transfer->sourceLocation->name}*\nOutlet tujuan: *{$transfer->destinationLocation->name}*\nDiterima oleh: *".($receipt->receiver?->name ?? 'Staff Outlet')."*\nWaktu terima: *{$receivedAt}*\nStatus: *".($receipt->is_final ? 'Penerimaan final' : 'Penerimaan sebagian')."*\n\n*Jumlah yang diterima:*\n{$items}\n\nPeriksa riwayat transfer untuk rincian dan tindak lanjut bila masih ada sisa.";

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
        StockTransferReceiptWhatsAppLog::whereKey($this->logId)->update([
            'status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    private function requeue(string $message): void
    {
        StockTransferReceiptWhatsAppLog::whereKey($this->logId)->update([
            'status' => $this->attempts() >= $this->tries ? 'failed' : 'queued',
            'error_message' => mb_substr($message, 0, 2000),
        ]);
    }
}
