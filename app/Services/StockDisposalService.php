<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PurchaseInvoice;
use App\Models\StockDisposal;
use App\Models\StockDisposalItem;
use App\Models\StockDisposalSupplierResolution;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockDisposalService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function submit(array $data): StockDisposal
    {
        return DB::transaction(function () use ($data) {
            Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            if ($data['type'] === 'supplier_return') {
                $this->assertSupplierReturnOrigin($data);
            }
            $movements = StockMovement::query()->where('location_id', $data['location_id'])
                ->where('product_id', $data['product_id'])->lockForUpdate()->get(['type', 'quantity', 'batch_id']);
            $balance = fn ($rows) => (float) $rows->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
            $physical = $balance($movements->where('batch_id', ! empty($data['batch_id']) ? (int) $data['batch_id'] : null));
            $pendingQuery = DB::table('stock_disposal_items as items')->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
                ->where('disposals.status', 'pending_approval')->where('disposals.location_id', $data['location_id'])->where('items.product_id', $data['product_id']);
            if (! empty($data['batch_id'])) $pendingQuery->where('items.batch_id', $data['batch_id']);
            else $pendingQuery->whereNull('items.batch_id');
            $held = (float) $pendingQuery->sum('items.quantity');
            if (! empty($data['batch_id'])) {
                $batchBelongs = DB::table('stock_in_items as batches')->join('stock_ins as receipts', 'receipts.id', '=', 'batches.stock_in_id')
                    ->where('batches.id', $data['batch_id'])->where('batches.product_id', $data['product_id'])->where('receipts.location_id', $data['location_id'])->exists();
                if (! $batchBelongs) throw ValidationException::withMessages(['batch_id' => 'Batch tidak cocok dengan produk dan lokasi yang dipilih.']);
            }
            if ($physical - $held + 0.0005 < (float) $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah melebihi stok yang belum dikarantina pada lokasi/batch ini.']);
            }
            $disposal = StockDisposal::create([
                'reference' => $data['reference'],
                'type' => $data['type'],
                'status' => 'pending_approval',
                'location_id' => $data['location_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'reason_category' => $data['reason_category'],
                'reason' => $data['reason'],
                'created_by' => auth()->id(),
            ]);
            $disposal->items()->create([
                'product_id' => $data['product_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'quantity' => $data['quantity'],
            ]);
            $this->auditLogger->record('stock_disposal.submitted', $disposal, [
                'reference' => $disposal->reference,
                'type' => $disposal->type,
                'location_id' => $disposal->location_id,
                'product_id' => $data['product_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'quantity' => $data['quantity'],
                'reason_category' => $disposal->reason_category,
                'reason' => $disposal->reason,
            ]);

            return $disposal;
        });
    }

    public function approve(StockDisposal $disposal, ?string $reviewNote = null): StockDisposal
    {
        return DB::transaction(function () use ($disposal, $reviewNote) {
            $disposal = StockDisposal::with('items')->lockForUpdate()->findOrFail($disposal->id);
            if ($disposal->status !== 'pending_approval') {
                throw ValidationException::withMessages(['disposal' => 'Hanya pengajuan yang menunggu review yang dapat disetujui.']);
            }
            if ((int) $disposal->created_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['disposal' => 'Pengaju tidak dapat menyetujui write-off/retur yang dibuat sendiri.']);
            }
            Location::whereKey($disposal->location_id)->lockForUpdate()->firstOrFail();

            foreach ($disposal->items as $item) {
                StockOpnameMovementGuard::assertProductCanMove((int) $disposal->location_id, (int) $item->product_id);
                if ($disposal->type === 'supplier_return') {
                    $this->assertSupplierReturnOrigin([
                        'purchase_invoice_id' => $disposal->purchase_invoice_id,
                        'batch_id' => $item->batch_id,
                        'product_id' => $item->product_id,
                        'location_id' => $disposal->location_id,
                        'quantity' => $item->quantity,
                    ], (int) $disposal->id);
                }
                $movements = StockMovement::query()->where('location_id', $disposal->location_id)
                    ->where('product_id', $item->product_id)->lockForUpdate()->get(['type', 'quantity', 'batch_id']);
                $available = $item->batch_id
                    ? $movements->where('batch_id', $item->batch_id)->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity)
                    : $movements->whereNull('batch_id')->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
                $otherPending = DB::table('stock_disposal_items as items')->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
                    ->where('disposals.status', 'pending_approval')->where('disposals.location_id', $disposal->location_id)
                    ->where('items.product_id', $item->product_id)->where('items.id', '<>', $item->id)
                    ->when($item->batch_id, fn ($query) => $query->where('items.batch_id', $item->batch_id), fn ($query) => $query->whereNull('items.batch_id'))
                    ->sum('items.quantity');
                if ($available - (float) $otherPending + 0.0005 < (float) $item->quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Stok tersedia saat approval kurang dari jumlah yang diajukan. Muat ulang dan periksa transaksi terbaru.']);
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $disposal->location_id,
                    'user_id' => auth()->id(),
                    'batch_id' => $item->batch_id,
                    'stock_disposal_item_id' => $item->id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'note' => ucfirst(str_replace('_', ' ', $disposal->type)).' '.$disposal->reference.' — '.$disposal->reason_category,
                ]);
            }

            $disposal->update([
                'status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $reviewNote,
            ]);
            $this->auditLogger->record('stock_disposal.approved', $disposal, [
                'status_before' => 'pending_approval', 'status_after' => 'approved', 'review_note' => $reviewNote,
            ]);

            return $disposal;
        });
    }

    public function reject(StockDisposal $disposal, string $reviewNote): StockDisposal
    {
        return DB::transaction(function () use ($disposal, $reviewNote) {
            $disposal = StockDisposal::query()->lockForUpdate()->findOrFail($disposal->id);
            if ($disposal->status !== 'pending_approval') {
                throw ValidationException::withMessages(['disposal' => 'Hanya pengajuan yang menunggu review yang dapat ditolak.']);
            }
            if ((int) $disposal->created_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['disposal' => 'Pengaju tidak dapat menolak pengajuan yang dibuat sendiri.']);
            }
            $disposal->update([
                'status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $reviewNote,
            ]);
            $this->auditLogger->record('stock_disposal.rejected', $disposal, [
                'status_before' => 'pending_approval', 'status_after' => 'rejected', 'review_note' => $reviewNote,
            ]);

            return $disposal;
        });
    }

    public function recordSupplierResolution(StockDisposal $disposal, array $data): StockDisposalSupplierResolution
    {
        return DB::transaction(function () use ($disposal, $data) {
            $disposal = StockDisposal::query()->lockForUpdate()->findOrFail($disposal->id);
            if ($disposal->type !== 'supplier_return' || $disposal->status !== 'approved' || ! $disposal->purchase_invoice_id) {
                throw ValidationException::withMessages(['disposal' => 'Penyelesaian supplier hanya dapat dicatat untuk retur yang sudah disetujui.']);
            }

            $item = StockDisposalItem::query()->where('stock_disposal_id', $disposal->id)
                ->lockForUpdate()->findOrFail($data['stock_disposal_item_id']);
            $alreadyResolved = (float) $item->supplierResolutions()->sum('quantity');
            if ($alreadyResolved + (float) $data['quantity'] > (float) $item->quantity + 0.0005) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah penyelesaian melebihi sisa klaim supplier yang belum diselesaikan.']);
            }

            if (in_array($data['resolution_type'], ['credit_note', 'refund'], true) && (float) ($data['amount'] ?? 0) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Credit note atau refund wajib mencatat nilai Rupiah lebih dari nol.']);
            }
            if (! in_array($data['resolution_type'], ['credit_note', 'refund'], true) && ! empty($data['amount'])) {
                throw ValidationException::withMessages(['amount' => 'Nilai Rupiah hanya dicatat untuk credit note atau refund.']);
            }
            if ($data['resolution_type'] === 'claim_rejected' && blank($data['note'] ?? null)) {
                throw ValidationException::withMessages(['note' => 'Jelaskan alasan supplier menolak klaim.']);
            }
            if (! empty($data['reference']) && $item->supplierResolutions()->where('resolution_type', $data['resolution_type'])->where('reference', $data['reference'])->exists()) {
                throw ValidationException::withMessages(['reference' => 'Bukti supplier ini sudah pernah dicatat pada item retur tersebut.']);
            }

            $replacementStockInId = null;
            if ($data['resolution_type'] === 'replacement') {
                $receipt = StockIn::with('items')->whereKey($data['replacement_stock_in_id'] ?? null)->lockForUpdate()->first();
                $receiptItem = $receipt?->items->firstWhere('product_id', (int) $item->product_id);
                $invoice = PurchaseInvoice::findOrFail($disposal->purchase_invoice_id);
                $alreadyLinked = (float) StockDisposalSupplierResolution::query()
                    ->where('replacement_stock_in_id', $receipt?->id)
                    ->whereHas('item', fn ($query) => $query->where('product_id', $item->product_id))->sum('quantity');
                if (! $receipt || $receipt->status !== 'approved' || (int) $receipt->location_id !== (int) $disposal->location_id
                    || (int) $receipt->supplier_id !== (int) $invoice->supplier_id || ! $receiptItem
                    || $alreadyLinked + (float) $data['quantity'] > (float) $receiptItem->quantity + 0.0005) {
                    throw ValidationException::withMessages(['replacement_stock_in_id' => 'Pilih receipt pengganti yang sudah disetujui, berasal dari supplier dan lokasi yang sama, dan jumlahnya mencukupi.']);
                }
                if ($item->supplierResolutions()->where('replacement_stock_in_id', $receipt->id)->exists()) {
                    throw ValidationException::withMessages(['replacement_stock_in_id' => 'Receipt pengganti ini sudah ditautkan ke item klaim tersebut.']);
                }
                $replacementStockInId = $receipt->id;
            } elseif (! empty($data['replacement_stock_in_id'])) {
                throw ValidationException::withMessages(['replacement_stock_in_id' => 'Receipt pengganti hanya berlaku untuk hasil berupa penggantian barang.']);
            }

            $resolution = $item->supplierResolutions()->create([
                'resolution_type' => $data['resolution_type'],
                'quantity' => $data['quantity'],
                'amount' => $data['amount'] ?? null,
                'reference' => $data['reference'] ?? null,
                'replacement_stock_in_id' => $replacementStockInId,
                'recorded_by' => auth()->id(),
                'resolved_at' => $data['resolved_at'] ?? now(),
                'note' => $data['note'] ?? null,
            ]);
            $this->auditLogger->record('stock_disposal.supplier_resolution_recorded', $disposal, [
                'resolution_id' => $resolution->id,
                'disposal_item_id' => $item->id,
                'resolution_type' => $resolution->resolution_type,
                'quantity' => $resolution->quantity,
                'amount' => $resolution->amount,
                'reference' => $resolution->reference,
                'replacement_stock_in_id' => $resolution->replacement_stock_in_id,
            ]);

            return $resolution;
        });
    }

    private function assertSupplierReturnOrigin(array $data, ?int $excludeDisposalId = null): void
    {
        $invoice = PurchaseInvoice::query()->whereKey($data['purchase_invoice_id'])->lockForUpdate()->firstOrFail();
        $batch = StockInItem::query()->with('stockIn')->whereKey($data['batch_id'])->lockForUpdate()->firstOrFail();
        $receipt = $batch->stockIn;
        if ($invoice->status !== 'accepted' || ! $invoice->purchase_order_id
            || (int) $batch->product_id !== (int) $data['product_id']
            || (int) $receipt->location_id !== (int) $data['location_id']
            || $receipt->status !== 'approved'
            || (int) $receipt->purchase_invoice_id !== (int) $invoice->id
            || (int) $receipt->purchase_order_id !== (int) $invoice->purchase_order_id
            || (int) $receipt->supplier_id !== (int) $invoice->supplier_id
            || ! $invoice->items()->where('product_id', $data['product_id'])->exists()) {
            throw ValidationException::withMessages(['purchase_invoice_id' => 'Retur wajib menunjuk invoice accepted dan receipt approved dari supplier, PO, produk, lokasi, serta batch asal yang sama.']);
        }

        $reserved = (float) DB::table('stock_disposal_items as items')
            ->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
            ->where('disposals.type', 'supplier_return')
            ->where('disposals.purchase_invoice_id', $invoice->id)
            ->whereIn('disposals.status', ['pending_approval', 'approved'])
            ->where('items.batch_id', $batch->id)
            ->when($excludeDisposalId, fn ($query) => $query->where('disposals.id', '<>', $excludeDisposalId))
            ->sum('items.quantity');
        if ($reserved + (float) $data['quantity'] > (float) $batch->quantity + 0.0005) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah retur melebihi kuantitas receipt batch yang diterima setelah retur pending dan approved diperhitungkan.']);
        }
    }
}
