<?php

namespace App\Services;

use App\Models\Location;
use App\Models\StockMovement;
use App\Models\StockOut;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    /** Validate stock without creating a Stock Out or movements. */
    public function assertAvailable(array $data): void
    {
        DB::transaction(function () use ($data) {
            Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $this->allocateAvailableStock($data);
        });
    }

    public function issue(array $data): StockOut
    {
        return DB::transaction(function () use ($data) {
            // Stock In and Stock Out serialize on this row so the preview cannot overspend stale balances.
            Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $allocations = $this->allocateAvailableStock($data);

            $issue = StockOut::create([
                'reference' => $data['reference'],
                'destination' => $data['destination'],
                'purpose' => $data['purpose'] ?? null,
                'location_id' => $data['location_id'],
                'issued_by' => auth()->id(),
                'issued_at' => $data['issued_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($allocations as $allocation) {
                $item = $issue->items()->create($allocation);
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $issue->location_id,
                    'user_id' => auth()->id(),
                    'batch_id' => $item->batch_id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'note' => 'Stock Out '.$issue->reference.' (FEFO)',
                    'stock_out_item_id' => $item->id,
                ]);
            }

            $this->auditLogger->record('stock_out.issued', $issue, [
                'reference' => $issue->reference,
                'destination' => $issue->destination,
                'purpose' => $issue->purpose,
                'location_id' => $issue->location_id,
                'allocation_method' => 'FEFO',
                'item_count' => count($allocations),
                'allocations' => $allocations,
            ]);

            return $issue;
        });
    }

    /** Return FEFO allocations or fail if the requested quantity is unavailable. */
    private function allocateAvailableStock(array $data): array
    {
        $issuedDate = Carbon::parse($data['issued_at'])->toDateString();
        $requested = collect($data['items'])->groupBy('product_id')
            ->map(fn ($lines) => (float) $lines->sum(fn ($line) => (float) $line['quantity']));
        $allocations = [];
        $errors = [];

        foreach ($requested as $productId => $requestedQuantity) {
            StockOpnameMovementGuard::assertProductCanMove((int) $data['location_id'], (int) $productId);
            $movements = StockMovement::where('product_id', $productId)
                ->where('location_id', $data['location_id'])
                ->lockForUpdate()
                ->get(['type', 'quantity', 'batch_id']);
            $balanceOf = fn ($rows) => $rows->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
            $totalBalance = (float) $balanceOf($movements);

            if ($totalBalance + 0.0005 < $requestedQuantity) {
                $errors['items.'.$productId.'.quantity'] = 'Total stok produk di lokasi hanya '.\App\Support\QuantityFormatter::format(max(0, $totalBalance)).'.';
                continue;
            }

            $batchBalances = DB::table('stock_in_items as batches')
                ->join('stock_ins as receipts', 'receipts.id', '=', 'batches.stock_in_id')
                ->leftJoin('stock_movements as movements', function ($join) use ($data, $productId) {
                    $join->on('movements.batch_id', '=', 'batches.id')
                        ->where('movements.location_id', '=', $data['location_id'])
                        ->where('movements.product_id', '=', $productId);
                })
                ->where('receipts.location_id', $data['location_id'])
                ->where('batches.product_id', $productId)
                ->selectRaw("batches.id, batches.expiry_date, COALESCE(batches.batch_received_at, receipts.received_at) as received_at, COALESCE(SUM(CASE WHEN movements.type = 'in' THEN movements.quantity WHEN movements.type = 'out' THEN -movements.quantity ELSE 0 END), 0) as balance")
                ->groupBy('batches.id', 'batches.expiry_date', 'batches.batch_received_at', 'receipts.received_at')
                ->havingRaw("COALESCE(SUM(CASE WHEN movements.type = 'in' THEN movements.quantity WHEN movements.type = 'out' THEN -movements.quantity ELSE 0 END), 0) > 0")
                ->orderByRaw('batches.expiry_date IS NULL')
                ->orderBy('batches.expiry_date')
                ->orderByRaw('COALESCE(batches.batch_received_at, receipts.received_at)')
                ->orderBy('batches.id')
                ->get();

            $trackedBalance = (float) $balanceOf($movements->whereNotNull('batch_id'));
            $remaining = $requestedQuantity;

            foreach ($batchBalances as $batch) {
                if ($batch->expiry_date && Carbon::parse($batch->expiry_date)->toDateString() < $issuedDate) continue;
                $onHold = (float) DB::table('stock_disposal_items as items')
                    ->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
                    ->where('disposals.status', 'pending_approval')
                    ->where('disposals.location_id', $data['location_id'])
                    ->where('items.product_id', $productId)
                    ->where('items.batch_id', $batch->id)->sum('items.quantity');
                $available = max(0, (float) $batch->balance - $onHold);
                $quantity = min($remaining, $available);
                if ($quantity <= 0) continue;

                $allocations[] = ['product_id' => $productId, 'batch_id' => $batch->id, 'quantity' => $quantity];
                $remaining -= $quantity;
                if ($remaining <= 0.0005) break;
            }

            // Legacy receipts without batch metadata remain usable after tracked FEFO batches.
            $untrackedBalance = max(0, $totalBalance - $trackedBalance);
            $untrackedHold = (float) DB::table('stock_disposal_items as items')
                ->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
                ->where('disposals.status', 'pending_approval')
                ->where('disposals.location_id', $data['location_id'])
                ->where('items.product_id', $productId)->whereNull('items.batch_id')->sum('items.quantity');
            $untracked = max(0, $untrackedBalance - $untrackedHold);
            if ($remaining > 0.0005 && $untracked > 0) {
                $quantity = min($remaining, $untracked);
                $allocations[] = ['product_id' => $productId, 'batch_id' => null, 'quantity' => $quantity];
                $remaining -= $quantity;
            }

            if ($remaining > 0.0005) {
                $errors['items.'.$productId.'.quantity'] = 'Stok yang masih layak dikeluarkan menurut FEFO hanya '.\App\Support\QuantityFormatter::format($requestedQuantity - $remaining).'. Batch kedaluwarsa tidak dialokasikan.';
            }
        }

        if ($errors) throw ValidationException::withMessages($errors);

        return $allocations;
    }
}
