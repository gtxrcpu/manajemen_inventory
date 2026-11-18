<?php

namespace App\Jobs;

use App\Contracts\NotificationProvider;
use App\Models\StockTransfer;
use App\Models\StockTransferApprovalWhatsAppLog;
use App\Services\TransferApprovalAlertService;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendTransferApprovalWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $logId) {}

    public function backoff(): array { return [30, 120]; }

    public function handle(NotificationProvider $provider, TransferApprovalAlertService $alerts): void
    {
        $log = StockTransferApprovalWhatsAppLog::with('transfer')->findOrFail($this->logId);
        if ($log->transfer->status !== 'pending_approval') {
            $log->update(['status' => 'failed', 'error_message' => 'Transfer tidak lagi menunggu persetujuan PIC Gudang.']);
            return;
        }
        if (! $alerts->isRecipientForTransfer($log->transfer, $log->recipient)) {
            $log->update(['status' => 'failed', 'error_message' => 'Alert persetujuan dinonaktifkan atau nomor PIC Gudang tidak lagi terdaftar.']);
            return;
        }

        $claimed = DB::transaction(function () {
            $current = StockTransferApprovalWhatsAppLog::query()->whereKey($this->logId)->lockForUpdate()->first();
            if (! $current || $current->status !== 'queued') return false;
            $current->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return true;
        });
        if (! $claimed) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $this->requeue('WhatsApp sedang tidak terhubung.');
            throw new RuntimeException('WhatsApp sedang tidak terhubung. Permintaan persetujuan transfer akan dicoba kembali.');
        }

        $transfer = StockTransfer::with(['sourceLocation', 'destinationLocation', 'preparedBy', 'items.product.unit'])
            ->findOrFail($log->stock_transfer_id);
        $items = $transfer->items->map(fn ($item) => '• '.$item->product->name.': '.QuantityFormatter::format($item->quantity).' '.($item->product->unit->symbol ?? 'unit'))->implode("\n");
        $message = "📋 *PERSETUJUAN TRANSFER GUDANG*\n_Sistem Inventaris Technolife_\n\n"
            ."Referensi: *{$transfer->reference}*\nGudang asal: *{$transfer->sourceLocation->name}*\nOutlet tujuan: *{$transfer->destinationLocation->name}*\nDisiapkan oleh: *".($transfer->preparedBy?->name ?? 'Staff Gudang')."*\n\n*Barang yang diminta untuk dikirim:*\n{$items}\n\n"
            .'Transfer belum mengurangi stok gudang. Silakan tinjau dan setujui dari menu Distribusi Stok.';

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
        StockTransferApprovalWhatsAppLog::whereKey($this->logId)->update([
            'status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    private function requeue(string $message): void
    {
        StockTransferApprovalWhatsAppLog::whereKey($this->logId)->update([
            'status' => $this->attempts() >= $this->tries ? 'failed' : 'queued',
            'error_message' => mb_substr($message, 0, 2000),
        ]);
    }
}
