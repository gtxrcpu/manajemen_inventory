<?php

namespace App\Services;

use App\Models\Location;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function apply(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $movements = StockMovement::where('location_id', $data['location_id'])
                ->where('product_id', $data['product_id'])->lockForUpdate()->get(['type', 'quantity']);
            $oldQuantity = $movements->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
            if ($data['direction'] === 'decrease' && (float) $data['quantity'] > $oldQuantity) {
                throw ValidationException::withMessages(['quantity' => 'Stok tersedia hanya '.number_format($oldQuantity, 3, ',', '.').'. Penyesuaian tidak boleh membuat saldo minus.']);
            }

            $delta = $data['direction'] === 'increase' ? (float) $data['quantity'] : -(float) $data['quantity'];
            $adjustment = StockAdjustment::create($data + [
                'created_by' => auth()->id(),
                'old_quantity' => $oldQuantity,
                'new_quantity' => $oldQuantity + $delta,
            ]);
            StockMovement::create([
                'product_id' => $adjustment->product_id,
                'location_id' => $adjustment->location_id,
                'user_id' => auth()->id(),
                'type' => $adjustment->direction === 'increase' ? 'in' : 'out',
                'quantity' => $adjustment->quantity,
                'note' => 'Penyesuaian '.$adjustment->reference.' — '.$adjustment->reason,
                'stock_adjustment_id' => $adjustment->id,
            ]);
            $this->auditLogger->record('stock_adjustment.applied', $adjustment, [
                'reference' => $adjustment->reference,
                'product_id' => $adjustment->product_id,
                'location_id' => $adjustment->location_id,
                'old_quantity' => $adjustment->old_quantity,
                'delta' => $delta,
                'new_quantity' => $adjustment->new_quantity,
                'reason' => $adjustment->reason,
            ]);
            return $adjustment;
        });
    }
}
