<?php

namespace App\Services;

use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockInService
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly LowStockAlertService $lowStockAlerts,
        private readonly StockInApprovalAlertService $approvalAlerts,
    ) {}

    public function receive(array $data, ?bool $requiresOutletApproval = null): StockIn
    {
        $receipt = DB::transaction(function () use ($data, $requiresOutletApproval) {
            $location = Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $requiresOutletApproval ??= $location->type === Location::TYPE_OUTLET;
            if ($requiresOutletApproval && $location->type !== Location::TYPE_OUTLET) {
                throw \Illuminate\Validation\ValidationException::withMessages(['location_id' => 'Persetujuan PIC hanya berlaku untuk penerimaan yang ditujukan ke outlet.']);
            }
            $expiryTrackedIds = Product::whereIn('id', collect($data['items'])->pluck('product_id')->unique())
                ->where('requires_expiry_tracking', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($data['items'] as $index => $line) {
                if ((float) ($line['quantity'] ?? 0) > 0
                    && in_array((int) $line['product_id'], $expiryTrackedIds, true)
                    && (blank($line['batch_number'] ?? null) || blank($line['expiry_date'] ?? null))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.batch_number" => 'Produk ini wajib dicatat nomor batch dan tanggal kedaluwarsanya.',
                    ]);
                }
            }
            if (! $requiresOutletApproval) {
                foreach ($data['items'] as $line) {
                    StockOpnameMovementGuard::assertProductCanMove((int) $location->id, (int) $line['product_id']);
                }
            }
            $receipt = StockIn::create([
                'reference' => $data['reference'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'source' => $data['source'],
                'location_id' => $data['location_id'],
                'received_by' => auth()->id(),
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
                'status' => $requiresOutletApproval ? 'pending_approval' : 'approved',
            ]);

            foreach ($data['items'] as $line) {
                $item = $receipt->items()->create($line);
                if ($requiresOutletApproval || (float) $item->quantity <= 0) continue;

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $receipt->location_id,
                    'user_id' => auth()->id(),
                    // Every receipt line is a lot for balance/age tracking, even without a printed batch or expiry.
                    'batch_id' => $item->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'note' => 'Stock In '.$receipt->reference,
                    'stock_in_item_id' => $item->id,
                    'created_at' => $receipt->received_at,
                    'updated_at' => now(),
                ]);
            }

            $this->auditLogger->record($requiresOutletApproval ? 'stock_in.submitted_for_approval' : 'stock_in.received', $receipt, [
                'reference' => $receipt->reference,
                'location_id' => $receipt->location_id,
                'status' => $receipt->status,
                'item_count' => count($data['items']),
                'quantities' => collect($data['items'])->map(fn ($line) => [
                    'product_id' => $line['product_id'],
                    'delivered_quantity' => $line['delivered_quantity'] ?? $line['quantity'],
                    'accepted_quantity' => $line['quantity'],
                    'variance_note' => $line['variance_note'] ?? null,
                ])->all(),
            ]);

            return $receipt;
        });

        // Evaluate alerts only after the receipt and its movements have committed.
        if ($receipt->status === 'approved') {
            foreach (collect($data['items'])->pluck('product_id')->unique() as $productId) {
                $this->lowStockAlerts->sync((int) $productId, (int) $receipt->location_id);
            }
        } elseif ($receipt->status === 'pending_approval') {
            $this->approvalAlerts->queue($receipt);
        }

        return $receipt;
    }
}
