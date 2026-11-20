<?php

namespace App\Services;

use App\Models\StockIn;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInApprovalService
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly LowStockAlertService $lowStockAlerts,
        private readonly PurchasingService $purchasingService,
    ) {}

    public function approve(StockIn $stockIn): StockIn
    {
        $receipt = DB::transaction(function () use ($stockIn) {
            $receipt = StockIn::with('items')->lockForUpdate()->findOrFail($stockIn->id);
            $this->assertPending($receipt);
            $this->assertReviewerCanDecide($receipt);
            Location::whereKey($receipt->location_id)->lockForUpdate()->firstOrFail();

            foreach ($receipt->items as $item) {
                if ((float) $item->quantity <= 0) continue;
                StockOpnameMovementGuard::assertProductCanMove((int) $receipt->location_id, (int) $item->product_id);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $receipt->location_id,
                    'user_id' => auth()->id(),
                    'batch_id' => $item->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'note' => 'Stock In '.$receipt->reference.' disetujui PIC',
                    'stock_in_item_id' => $item->id,
                    'created_at' => $receipt->received_at,
                    'updated_at' => now(),
                ]);
            }

            $receipt->update(['status' => 'approved', 'decision_by' => auth()->id(), 'decided_at' => now(), 'rejection_reason' => null, 'rejection_category' => null]);
            if ($receipt->purchase_order_id) $this->purchasingService->syncReceiptProgress((int) $receipt->purchase_order_id);
            $this->auditLogger->record('stock_in.approved', $receipt, [
                'status_before' => 'pending_approval',
                'status_after' => 'approved',
                'decision_by' => auth()->id(),
                'decided_at' => $receipt->decided_at->toISOString(),
            ]);

            return $receipt;
        });

        foreach ($receipt->items->pluck('product_id')->unique() as $productId) {
            $this->lowStockAlerts->sync((int) $productId, (int) $receipt->location_id);
        }

        return $receipt;
    }

    public function reject(StockIn $stockIn, string $category, string $reason): StockIn
    {
        return DB::transaction(function () use ($stockIn, $category, $reason) {
            $receipt = StockIn::lockForUpdate()->findOrFail($stockIn->id);
            $this->assertPending($receipt);
            $this->assertReviewerCanDecide($receipt);

            $receipt->update(['status' => 'rejected', 'decision_by' => auth()->id(), 'decided_at' => now(), 'rejection_category' => $category, 'rejection_reason' => $reason]);
            if ($receipt->purchase_order_id) $this->purchasingService->syncReceiptProgress((int) $receipt->purchase_order_id);
            $this->auditLogger->record('stock_in.rejected', $receipt, [
                'status_before' => 'pending_approval',
                'status_after' => 'rejected',
                'rejection_category' => $category,
                'decision_by' => auth()->id(),
                'decided_at' => $receipt->decided_at->toISOString(),
                'rejection_reason' => $reason,
            ]);

            return $receipt;
        });
    }

    private function assertPending(StockIn $receipt): void
    {
        if ($receipt->status !== 'pending_approval') {
            throw ValidationException::withMessages(['stock_in' => 'Stock In ini sudah diproses dan tidak dapat diputuskan kembali.']);
        }
    }

    private function assertReviewerCanDecide(StockIn $receipt): void
    {
        $user = auth()->user();
        $isAdmin = $user?->hasRole('super_admin') ?? false;
        $isAssignedPic = ($user?->hasRole('pic_outlet') ?? false)
            && $user->canAccessOutletLocation((int) $receipt->location_id);

        if (! $user || ! $user->hasPermissionTo('stock-in.approve') || (! $isAdmin && ! $isAssignedPic)) {
            abort(403);
        }
    }
}
