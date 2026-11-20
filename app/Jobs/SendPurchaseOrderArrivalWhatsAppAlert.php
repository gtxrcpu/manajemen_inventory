<?php

namespace App\Jobs;

use App\Contracts\NotificationProvider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderWhatsAppLog;
use App\Services\PurchaseOrderArrivalAlertService;
use App\Support\QuantityFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendPurchaseOrderArrivalWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $logId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(NotificationProvider $provider, PurchaseOrderArrivalAlertService $alerts): void
    {
        $log = PurchaseOrderWhatsAppLog::with('purchaseOrder')->findOrFail($this->logId);
        if (! in_array($log->purchaseOrder->status, ['open', 'partially_received', 'issued'], true)) {
            $log->update(['status' => 'failed', 'error_message' => 'PO sudah diterima; info kedatangan tidak lagi dikirim.']);
            return;
        }
        if (! $alerts->isRecipientForOrder($log->purchaseOrder, $log->recipient)) {
            $log->update(['status' => 'failed', 'error_message' => 'Notifikasi WhatsApp dinonaktifkan atau nomor penerima tidak lagi terdaftar.']);
            return;
        }

        $claimed = DB::transaction(function () {
            $current = PurchaseOrderWhatsAppLog::query()->whereKey($this->logId)->lockForUpdate()->first();
            if (! $current || $current->status !== 'queued') return false;

            $current->update(['status' => 'sending', 'error_message' => null, 'last_attempt_at' => now()]);
            return true;
        });
        if (! $claimed) return;

        if (! ($provider->status()['connected'] ?? false)) {
            $this->releaseAfterFailure('WhatsApp sedang tidak terhubung.');
            throw new RuntimeException('WhatsApp sedang tidak terhubung. Notifikasi PO akan dicoba kembali.');
        }

        $order = PurchaseOrder::with(['supplier', 'location', 'items.product.unit'])->findOrFail($log->purchase_order_id);
        $items = $order->items->map(function ($item) {
            $unit = $item->product->unit->symbol ?? 'unit';
            return '• '.$item->product->name.': '.QuantityFormatter::format($item->quantity).' '.$unit;
        })->implode("\n");
        $expected = $order->expected_delivery
            ? $order->expected_delivery->format('d/m/Y')
            : 'Belum ditentukan';
        $message = "📦 *INFO KEDATANGAN BARANG*\n_Sistem Inventaris Technolife_\n\n"
            ."PO: *{$order->reference}*\nSupplier: *{$order->supplier->name}*\nGudang: *{$order->location->name}*\n"
            ."Estimasi kedatangan: *{$expected}*\n\n*Daftar barang:*\n{$items}\n\n"
            .'Mohon siapkan penerimaan dan cocokkan jumlah serta kondisi barang saat tiba.';

        try {
            $messageId = $provider->send($log->recipient, $message);
            $log->update(['status' => 'sent', 'provider_message_id' => $messageId, 'sent_at' => now(), 'error_message' => null]);
        } catch (Throwable $exception) {
            $this->releaseAfterFailure($exception->getMessage());
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        PurchaseOrderWhatsAppLog::whereKey($this->logId)->update([
            'status' => 'failed',
            'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    private function releaseAfterFailure(string $message): void
    {
        PurchaseOrderWhatsAppLog::whereKey($this->logId)->update([
            'status' => $this->attempts() >= $this->tries ? 'failed' : 'queued',
            'error_message' => mb_substr($message, 0, 2000),
        ]);
    }
}
