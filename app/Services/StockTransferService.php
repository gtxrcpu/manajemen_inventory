<?php

namespace App\Services;

use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferRequest;
use App\Models\StockTransferReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        private readonly StockOutService $stockOutService,
        private readonly StockInService $stockInService,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function request(array $data): StockTransferRequest
    {
        return DB::transaction(function () use ($data) {
            $location = Location::whereKey($data['location_id'])->where('is_active', true)->lockForUpdate()->first();
            if (! $location || $location->type !== Location::TYPE_OUTLET) {
                throw ValidationException::withMessages(['location_id' => 'Pilih outlet aktif.']);
            }
            $request = StockTransferRequest::create([
                'reference' => 'REQ-OUT-'.$location->code.'-'.now()->format('ymdHis').'-'.random_int(10, 99),
                'location_id' => $location->id,
                'requested_by' => auth()->id(),
                'status' => 'pending',
                'requested_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $item) $request->items()->create($item);
            $this->auditLogger->record('stock_transfer.requested', $request, ['reference' => $request->reference, 'location_id' => $location->id]);
            return $request;
        });
    }

    public function markPicking(StockTransferRequest $request): StockTransferRequest
    {
        return DB::transaction(function () use ($request) {
            $request = StockTransferRequest::lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') throw ValidationException::withMessages(['request' => 'Permintaan ini sudah mulai diproses.']);
            $request->update(['status' => 'picking', 'picked_by' => auth()->id(), 'picked_at' => now()]);
            $this->auditLogger->record('stock_transfer.picking_started', $request, ['reference' => $request->reference]);
            return $request;
        });
    }

    public function dispatch(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $locations = Location::whereIn('id', [$data['source_location_id'], $data['destination_location_id']])
                ->where('is_active', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $locations->get($data['source_location_id']);
            $destination = $locations->get($data['destination_location_id']);
            if (! $source || $source->type !== Location::TYPE_WAREHOUSE) throw ValidationException::withMessages(['source_location_id' => 'Lokasi asal harus berupa gudang aktif.']);
            if (! $destination || $destination->type !== Location::TYPE_OUTLET || $source->is($destination)) throw ValidationException::withMessages(['destination_location_id' => 'Lokasi tujuan harus berupa outlet aktif yang berbeda dari gudang asal.']);

            $request = null;
            if (! empty($data['request_id'])) {
                $request = StockTransferRequest::with('items')->lockForUpdate()->findOrFail($data['request_id']);
                if ($request->status !== 'picking' || (int) $request->location_id !== (int) $destination->id) throw ValidationException::withMessages(['request_id' => 'Permintaan harus berstatus picking dan outletnya harus sesuai.']);
                $requested = $request->items->keyBy('product_id');
                foreach ($data['items'] as $line) {
                    if (! $requested->has((int) $line['product_id']) || (float) $line['quantity'] > (float) $requested[(int) $line['product_id']]->quantity) {
                        throw ValidationException::withMessages(['items' => 'Jumlah picking tidak boleh melebihi permintaan outlet.']);
                    }
                }
            }

            $transfer = StockTransfer::create([
                'reference' => $data['reference'], 'source_location_id' => $source->id, 'destination_location_id' => $destination->id,
                'stock_transfer_request_id' => $request?->id, 'dispatched_by' => auth()->id(), 'status' => 'in_transit',
                'dispatched_at' => now(), 'notes' => $data['notes'] ?? null,
            ]);
            $stockOut = $this->stockOutService->issue([
                'reference' => 'TRF-OUT-'.$transfer->id, 'destination' => 'Transfer '.$transfer->reference.' ke '.$destination->name,
                'location_id' => $source->id, 'issued_at' => now()->toDateString(), 'notes' => $data['notes'] ?? null, 'items' => $data['items'],
            ]);
            foreach ($stockOut->items()->orderBy('id')->get() as $item) {
                $transfer->items()->create(['product_id' => $item->product_id, 'source_batch_id' => $item->batch_id, 'stock_out_item_id' => $item->id, 'quantity' => $item->quantity]);
            }
            $transfer->update(['stock_out_id' => $stockOut->id]);
            if ($request) $request->update(['status' => 'dispatched', 'stock_transfer_id' => $transfer->id]);
            $this->auditLogger->record('stock_transfer.dispatched', $transfer, [
                'reference' => $transfer->reference, 'source_location_id' => $source->id, 'destination_location_id' => $destination->id,
                'stock_out_id' => $stockOut->id, 'request_id' => $request?->id, 'item_count' => $transfer->items()->count(),
            ]);
            return $transfer;
        });
    }

    public function receive(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $transfer = StockTransfer::with(['sourceLocation', 'destinationLocation', 'items.sourceBatch.stockIn'])
                ->lockForUpdate()->findOrFail($transfer->id);
            if (! in_array($transfer->status, ['in_transit', 'partially_received'], true)) throw ValidationException::withMessages(['transfer' => 'Transfer ini sudah ditutup atau tidak lagi menunggu penerimaan.']);
            if (! $transfer->destinationLocation->is_active || $transfer->destinationLocation->type !== Location::TYPE_OUTLET) throw ValidationException::withMessages(['transfer' => 'Lokasi outlet tujuan sudah tidak aktif.']);

            $receivedAt = now();
            $lines = [];
            $byTransferItem = [];
            foreach ($transfer->items as $item) {
                $quantity = (float) ($data['items'][$item->id] ?? 0);
                $remaining = (float) $item->quantity - (float) $item->received_quantity;
                if ($quantity < 0 || $quantity > $remaining + 0.0005) throw ValidationException::withMessages(['items.'.$item->id => 'Jumlah diterima melebihi sisa barang yang dikirim.']);
                if ($quantity > 0) {
                    $byTransferItem[$item->id] = $item;
                    $batchReceivedAt = $item->sourceBatch?->batch_received_at ?? $item->sourceBatch?->stockIn?->received_at;
                    $lines[] = ['product_id' => $item->product_id, 'quantity' => $quantity, 'batch_number' => $item->sourceBatch?->batch_number,
                        'expiry_date' => $item->sourceBatch?->expiry_date?->toDateString(), 'batch_received_at' => $batchReceivedAt?->toDateString()];
                }
            }
            $isFinal = (bool) ($data['is_final'] ?? false);
            $hasRemaining = collect($transfer->items)->contains(fn ($item) => ((float) $item->quantity - (float) $item->received_quantity - (float) ($data['items'][$item->id] ?? 0)) > 0.0005);
            if ($isFinal && $hasRemaining && blank($data['discrepancy_reason'] ?? null)) throw ValidationException::withMessages(['discrepancy_reason' => 'Jelaskan selisih sebelum menutup penerimaan.']);
            if (! $isFinal && count($lines) === 0) throw ValidationException::withMessages(['items' => 'Masukkan jumlah yang diterima atau tutup penerimaan dengan alasan selisih.']);

            $receiptStockIn = null;
            if ($lines) {
                $receiptStockIn = $this->stockInService->receive([
                    'reference' => 'TRF-IN-'.$transfer->id.'-'.($transfer->receipts()->count() + 1), 'supplier_id' => null,
                    'source' => 'Transfer '.$transfer->reference.' dari '.$transfer->sourceLocation->name, 'location_id' => $transfer->destination_location_id,
                    'received_at' => $receivedAt->toDateString(), 'notes' => $data['notes'] ?? null, 'items' => $lines,
                ]);
            }
            $receipt = StockTransferReceipt::create(['stock_transfer_id' => $transfer->id, 'stock_in_id' => $receiptStockIn?->id,
                'received_by' => auth()->id(), 'received_at' => $receivedAt, 'is_final' => $isFinal || ! $hasRemaining, 'notes' => $data['notes'] ?? null]);
            $stockInItems = $receiptStockIn?->items()->orderBy('id')->get() ?? collect();
            $receiptIndex = 0;
            foreach ($byTransferItem as $index => $transferItem) {
                $stockInItem = $stockInItems->get($receiptIndex++);
                $transferItem->received_quantity = (float) $transferItem->received_quantity + (float) ($data['items'][$index] ?? 0);
                $transferItem->save();
                $receipt->items()->create(['stock_transfer_item_id' => $transferItem->id, 'stock_in_item_id' => $stockInItem?->id,
                    'quantity' => $data['items'][$index]]);
                if (! $transferItem->stock_in_item_id && $stockInItem) $transferItem->update(['stock_in_item_id' => $stockInItem->id, 'destination_batch_id' => $stockInItem->id]);
            }
            foreach ($transfer->items as $item) {
                $remaining = (float) $item->quantity - (float) $item->received_quantity;
                if ($isFinal && $remaining > 0.0005) $item->update(['discrepancy_quantity' => $remaining, 'discrepancy_reason' => $data['discrepancy_reason']]);
            }
            $allReceived = $transfer->items()->whereRaw('received_quantity < quantity')->doesntExist();
            $status = $allReceived ? 'received' : ($isFinal ? 'received_with_discrepancy' : 'partially_received');
            $transfer->update(['stock_in_id' => $transfer->stock_in_id ?? $receiptStockIn?->id, 'status' => $status,
                'received_by' => auth()->id(), 'received_at' => $isFinal || $allReceived ? $receivedAt : $transfer->received_at]);
            $this->auditLogger->record('stock_transfer.received', $transfer, [
                'reference' => $transfer->reference, 'stock_in_id' => $receiptStockIn?->id, 'receipt_id' => $receipt->id,
                'received_by' => auth()->id(), 'status' => $status, 'is_final' => $isFinal,
            ]);
            return $transfer;
        });
    }
}
