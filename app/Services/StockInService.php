<?php

namespace App\Services;

use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class StockInService
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly LowStockAlertService $lowStockAlerts,
    ) {}

    public function receive(array $data): StockIn
    {
        $receipt = DB::transaction(function () use ($data) {
            Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $receipt = StockIn::create([
                'reference' => $data['reference'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'source' => $data['source'],
                'location_id' => $data['location_id'],
                'received_by' => auth()->id(),
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $item = $receipt->items()->create($line);
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

            $this->auditLogger->record('stock_in.received', $receipt, [
                'reference' => $receipt->reference,
                'location_id' => $receipt->location_id,
                'item_count' => count($data['items']),
                'quantities' => collect($data['items'])->map(fn ($line) => ['product_id' => $line['product_id'], 'quantity' => $line['quantity']])->all(),
            ]);

            return $receipt;
        });

        // Evaluate alerts only after the receipt and its movements have committed.
        foreach (collect($data['items'])->pluck('product_id')->unique() as $productId) {
            $this->lowStockAlerts->sync((int) $productId, (int) $receipt->location_id);
        }

        return $receipt;
    }
}
