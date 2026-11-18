<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\Product;
use App\Models\StockIn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly StockInService $stockInService,
        private readonly PurchaseOrderArrivalAlertService $arrivalAlerts,
    ) {}

    public function createRequest(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data) {
            $request = PurchaseRequest::create([
                'reference' => $data['reference'], 'requested_by' => auth()->id(),
                'required_date' => $data['required_date'] ?? null, 'notes' => $data['notes'] ?? null,
                'status' => 'approved', 'reviewed_at' => now(),
            ]);
            $request->items()->createMany($data['items']);
            $this->auditLogger->record('purchasing.request_auto_approved', $request, [
                'reference' => $request->reference,
                'item_count' => count($data['items']),
                'status' => 'approved',
                'approved_by' => 'system',
            ]);
            return $request;
        });
    }

    public function createOrder(PurchaseRequest $request, array $data): PurchaseOrder
    {
        $order = DB::transaction(function () use ($request, $data) {
            $request = PurchaseRequest::with('items')->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'approved' || $request->purchaseOrder()->exists()) {
                throw ValidationException::withMessages(['purchase_request_id' => 'PR harus sudah disetujui otomatis dan belum memiliki PO.']);
            }
            $order = PurchaseOrder::create([
                'reference' => $data['reference'], 'purchase_request_id' => $request->id,
                'supplier_id' => $data['supplier_id'], 'location_id' => $data['location_id'],
                'created_by' => auth()->id(), 'expected_delivery' => $data['expected_delivery'] ?? null,
                'notes' => $data['notes'] ?? null, 'status' => 'issued',
            ]);
            $order->items()->createMany($request->items->map(fn ($item) => [
                'product_id' => $item->product_id, 'quantity' => $item->quantity,
            ])->all());
            $request->update(['status' => 'ordered']);
            $this->auditLogger->record('purchasing.order_issued', $order, ['reference' => $order->reference, 'purchase_request_id' => $request->id]);
            return $order;
        });

        $this->arrivalAlerts->queue($order);

        return $order;
    }

    public function recordSupplierFollowUp(PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $order = PurchaseOrder::with(['items', 'stockIn.items'])->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, ['issued', 'received'], true) || $order->stockIn->isEmpty()) {
                throw ValidationException::withMessages(['follow_up_action' => 'Tindak lanjut supplier hanya dapat dicatat setelah ada penerimaan dan PO belum ditutup.']);
            }

            $receivedByItem = $order->stockIn->flatMap(fn (StockIn $receipt) => $receipt->items)
                ->filter(fn ($line) => $line->purchase_order_item_id !== null)
                ->groupBy('purchase_order_item_id')
                ->map(fn ($lines) => (float) $lines->sum('quantity'));
            $hasRemaining = $order->items->contains(fn ($item) => (float) ($receivedByItem[$item->id] ?? 0) + 0.0005 < (float) $item->quantity);

            if ($data['action'] === 'close_shortage') {
                if ($order->status !== 'issued' || ! $hasRemaining) {
                    throw ValidationException::withMessages(['action' => 'PO hanya dapat ditutup dengan selisih jika masih ada jumlah pesanan yang belum diterima.']);
                }
                $order->status = 'closed_with_variance';
                $order->received_at = now();
            }

            $order->supplier_follow_up_action = $data['action'];
            $order->supplier_follow_up_due_at = $data['due_at'] ?? null;
            $order->supplier_follow_up_note = trim($data['note']);
            $order->supplier_follow_up_by = auth()->id();
            $order->supplier_follow_up_at = now();
            $order->save();

            $this->auditLogger->record('purchasing.supplier_follow_up_recorded', $order, [
                'reference' => $order->reference,
                'action' => $order->supplier_follow_up_action,
                'due_at' => $order->supplier_follow_up_due_at?->toDateString(),
                'note' => $order->supplier_follow_up_note,
                'status_after' => $order->status,
                'recorded_by' => auth()->id(),
            ]);

            return $order;
        });
    }

    public function receiveOrder(PurchaseOrder $order, ?int $invoiceId, string $receivedAt, array $receiptLines, string $idempotencyKey, array $evidencePhotos = []): StockIn
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($order, $invoiceId, $receivedAt, $receiptLines, $idempotencyKey, $evidencePhotos, &$storedPaths) {
            $order = PurchaseOrder::with(['items', 'supplier', 'stockIn.items'])->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previousSubmission = StockIn::where('idempotency_key', $idempotencyKey)->first();
            if ($previousSubmission) {
                if ((int) $previousSubmission->purchase_order_id !== (int) $order->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Kunci submit ini sudah digunakan untuk PO lain. Muat ulang halaman penerimaan.']);
                }

                return $previousSubmission;
            }

            if ($order->status !== 'issued') {
                throw ValidationException::withMessages(['purchase_order' => 'PO ini sudah selesai diterima.']);
            }
            $invoice = $invoiceId ? PurchaseInvoice::with('items')->whereKey($invoiceId)->lockForUpdate()->firstOrFail() : null;
            if ($invoice && ((int) $invoice->purchase_order_id !== (int) $order->id || (int) $invoice->supplier_id !== (int) $order->supplier_id)) {
                throw ValidationException::withMessages(['purchase_invoice_id' => 'Faktur tidak terhubung ke PO dan supplier ini. Muat ulang halaman lalu coba lagi.']);
            }
            $invoiceQuantities = $invoice?->items->keyBy('product_id') ?? collect();
            $expiryTrackedProductIds = Product::query()->whereIn('id', $order->items->pluck('product_id')->unique())
                ->where('requires_expiry_tracking', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

            $receivedByItem = $order->stockIn
                ->flatMap(fn (StockIn $stockIn) => $stockIn->items)
                ->filter(fn ($line) => $line->purchase_order_item_id !== null)
                ->groupBy('purchase_order_item_id')
                ->map(fn ($lines) => (int) round((float) $lines->sum('quantity') * 1000));

            $linesToReceive = [];
            $totalDelivered = 0;
            $allItemsComplete = true;
            foreach ($order->items as $item) {
                $line = $receiptLines[$item->id] ?? [];
                $accepted = (int) round((float) ($line['accepted_quantity'] ?? 0) * 1000);
                $delivered = (int) round((float) ($line['delivered_quantity'] ?? ($line['accepted_quantity'] ?? 0)) * 1000);
                $ordered = (int) round((float) $item->quantity * 1000);
                $alreadyAccepted = (int) ($receivedByItem[$item->id] ?? 0);
                $remaining = max(0, $ordered - $alreadyAccepted);
                $varianceNote = trim((string) ($line['variance_note'] ?? ''));

                if ($accepted > $delivered) {
                    throw ValidationException::withMessages(["items.{$item->id}.accepted_quantity" => 'Jumlah layak diterima tidak boleh melebihi jumlah barang yang datang.']);
                }
                if ($accepted > $remaining && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jumlah nyata melebihi sisa PO. Tambahkan catatan selisih sebelum menyimpan.']);
                }
                if ($delivered > $remaining && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Tambahkan catatan jika jumlah datang melebihi sisa PO.']);
                }
                if ($accepted < $delivered && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jelaskan barang yang tidak layak diterima.']);
                }
                if ($accepted > 0 && in_array((int) $item->product_id, $expiryTrackedProductIds, true)) {
                    if (blank($line['batch_number'] ?? null)) {
                        throw ValidationException::withMessages(["items.{$item->id}.batch_number" => 'Nomor batch wajib diisi untuk produk yang melacak kedaluwarsa.']);
                    }
                    if (blank($line['expiry_date'] ?? null)) {
                        throw ValidationException::withMessages(["items.{$item->id}.expiry_date" => 'Tanggal kedaluwarsa wajib diisi untuk produk ini.']);
                    }
                }

                $totalDelivered += $delivered;
                if ($alreadyAccepted + $accepted < $ordered) $allItemsComplete = false;
                if ($delivered <= 0) continue;

                $linesToReceive[] = [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'delivered_quantity' => $delivered / 1000,
                    'invoice_quantity' => (float) ($invoiceQuantities->get($item->product_id)?->quantity ?? 0),
                    'quantity' => $accepted / 1000,
                    'variance_note' => $varianceNote ?: null,
                    'batch_number' => $line['batch_number'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'batch_received_at' => $receivedAt,
                ];
            }

            if ($allItemsComplete && $invoice) {
                foreach ($order->items as $item) {
                    $alreadyAccepted = (int) ($receivedByItem[$item->id] ?? 0);
                    $line = $receiptLines[$item->id] ?? [];
                    $accepted = (int) round((float) ($line['accepted_quantity'] ?? 0) * 1000);
                    $invoiceQuantity = (int) round((float) ($invoiceQuantities->get($item->product_id)?->quantity ?? 0) * 1000);
                    $varianceNote = trim((string) ($line['variance_note'] ?? ''));

                    if (abs(($alreadyAccepted + $accepted) - $invoiceQuantity) > 0 && $varianceNote === '') {
                        throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jumlah akhir berbeda dari faktur. Tambahkan catatan selisih sebelum menyimpan.']);
                    }
                }
            }

            if ($totalDelivered <= 0 || $linesToReceive === []) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah barang yang benar-benar datang.']);
            }

            $receiptNumber = $order->stockIn->count() + 1;
            $receipt = $this->stockInService->receive([
                'reference' => 'GRN-PO-'.$order->id.'-'.str_pad((string) $receiptNumber, 3, '0', STR_PAD_LEFT),
                'idempotency_key' => $idempotencyKey,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'purchase_invoice_id' => $invoice?->id,
                'source' => $order->supplier->name,
                'location_id' => $order->location_id,
                'received_at' => $receivedAt,
                'notes' => 'Pemeriksaan barang aktual untuk PO '.$order->reference,
                'items' => $linesToReceive,
            ]);

            foreach ($evidencePhotos as $photo) {
                if (! $photo instanceof UploadedFile) continue;

                $path = $photo->store('stock-in-evidence/'.$receipt->id, 'local');
                $storedPaths[] = $path;
                $receipt->evidences()->create([
                    'file_path' => $path,
                    'original_name' => mb_substr(basename($photo->getClientOriginalName()), 0, 255),
                    'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $photo->getSize() ?: 0,
                ]);
            }

            if ($allItemsComplete) {
                $order->update(['status' => 'received', 'received_at' => now()]);
            }
            $this->auditLogger->record($allItemsComplete ? 'purchasing.order_received' : 'purchasing.order_partially_received', $order, [
                'stock_in_id' => $receipt->id,
                'status' => $allItemsComplete ? 'received' : 'issued',
                'delivered_quantity_total' => $totalDelivered / 1000,
                'accepted_quantity_total' => collect($linesToReceive)->sum('quantity'),
                'purchase_invoice_id' => $invoice?->id,
                'purchase_invoice_number' => $invoice?->invoice_number,
                'evidence_photo_count' => count($storedPaths),
                'invoice_link_status' => $invoice ? 'linked' : 'pending_document',
                'invoice_quantities' => collect($linesToReceive)->map(fn ($line) => [
                    'product_id' => $line['product_id'],
                    'invoice_quantity' => $line['invoice_quantity'],
                    'received_quantity' => $line['quantity'],
                ])->all(),
            ]);
            return $receipt;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }
    }
}
