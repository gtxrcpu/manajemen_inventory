<?php

namespace App\Jobs;

use App\Contracts\NotificationProvider;
use App\Models\StockIn;
use App\Models\StockInApprovalWhatsAppLog;
use App\Services\StockInApprovalAlertService;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendStockInApprovalWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $logId) {}

    public function backoff(): array { return [30, 120]; }

    public function handle(NotificationProvider $provider, StockInApprovalAlertService $alerts): void
    {
        $log = StockInApprovalWhatsAppLog::with('stockIn')->findOrFail($this->logId);
        if ($log->stockIn->status !== 'pending_approval') {
            $log->update(['status' => 'failed', 'error_message' => 'Stock In sudah tidak menunggu persetujuan PIC Outlet.']);
            return;
        }
        if (! $alerts->isRecipientForStockIn($log->stockIn, $log->recipient)) {
            $log->update(['status' => 'failed', 'error_message' => 'Alert dinonaktifkan atau nomor PIC tidak lagi terdaftar untuk outlet ini.']);
            return;
        }

        $claimed = DB::transaction(function () {
            $current = StockInApprovalWhatsAppLog::query()->whereKey($this->logId)->lockForUpdate()->first();
            if (! $current || $current->status !== 'queued') return false;
            $current->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return true;
        });
        if (! $claimed) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $this->requeue('WhatsApp sedang tidak terhubung.');
            throw new RuntimeException('WhatsApp sedang tidak terhubung. Persetujuan Stock In akan dicoba kembali.');
        }

        $receipt = StockIn::with(['location', 'supplier', 'receiver', 'items.product.unit'])->findOrFail($log->stock_in_id);
        $items = $receipt->items->map(fn ($item) => '• '.$item->product->name.': '.QuantityFormatter::format($item->quantity).' '.($item->product->unit->symbol ?? 'unit'))->implode("\n");
        $message = "📥 *PERSETUJUAN STOCK IN OUTLET*\n_Sistem Inventaris Technolife_\n\n"
            ."Referensi: *{$receipt->reference}*\nOutlet: *{$receipt->location->name}*\nSumber: *".($receipt->supplier?->name ?? $receipt->source)."*\nDicatat oleh: *".($receipt->receiver?->name ?? 'Staff')."*\n\n*Barang diterima:*\n{$items}\n\n"
            .'Saldo outlet belum bertambah. Silakan tinjau dan setujui atau tolak dari menu Stock In.';

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
        StockInApprovalWhatsAppLog::whereKey($this->logId)->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000)]);
    }

    private function requeue(string $message): void
    {
        StockInApprovalWhatsAppLog::whereKey($this->logId)->update([
            'status' => $this->attempts() >= $this->tries ? 'failed' : 'queued',
            'error_message' => mb_substr($message, 0, 2000),
        ]);
    }
}
