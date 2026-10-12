<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\StockIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger, private readonly StockInService $stockInService) {}

    public function createRequest(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data) {
            $request = PurchaseRequest::create([
                'reference' => $data['reference'], 'requested_by' => auth()->id(),
                'required_date' => $data['required_date'] ?? null, 'notes' => $data['notes'] ?? null,
                'status' => 'pending_approval',
            ]);
            $request->items()->createMany($data['items']);
            $this->auditLogger->record('purchasing.request_submitted', $request, ['reference' => $request->reference, 'item_count' => count($data['items'])]);
            return $request;
        });
    }

    public function reviewRequest(PurchaseRequest $request, string $decision, ?string $note = null): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $decision, $note) {
            $request = PurchaseRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending_approval') {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah pernah ditinjau.']);
            }
            if ((int) $request->requested_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['review_note' => 'Pembuat PR tidak dapat menyetujui atau menolak pengajuannya sendiri.']);
            }
            $request->update([
                'status' => $decision,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
            $this->auditLogger->record('purchasing.request_'.$decision, $request, ['note' => $note]);
            return $request;
        });
    }

    public function createOrder(PurchaseRequest $request, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($request, $data) {
            $request = PurchaseRequest::with('items')->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'approved' || $request->purchaseOrder()->exists()) {
                throw ValidationException::withMessages(['purchase_request_id' => 'PR harus disetujui dan belum memiliki PO.']);
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
    }

    public function receiveOrder(PurchaseOrder $order, string $receivedAt, array $batchDetails = []): StockIn
    {
        return DB::transaction(function () use ($order, $receivedAt, $batchDetails) {
            $order = PurchaseOrder::with(['items', 'supplier'])->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'issued') {
                throw ValidationException::withMessages(['purchase_order' => 'PO ini sudah diterima.']);
            }
            $receipt = $this->stockInService->receive([
                'reference' => 'GRN-PO-'.$order->id,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'source' => $order->supplier->name,
                'location_id' => $order->location_id,
                'received_at' => $receivedAt,
                'notes' => 'Penerimaan penuh untuk PO '.$order->reference,
                'items' => $order->items->map(fn ($item) => array_merge(
                    ['product_id' => $item->product_id, 'quantity' => $item->quantity],
                    $batchDetails[$item->id] ?? [],
                ))->all(),
            ]);
            $order->update(['status' => 'received', 'received_at' => now()]);
            $this->auditLogger->record('purchasing.order_received', $order, ['stock_in_id' => $receipt->id]);
            return $receipt;
        });
    }
}
