<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Services\LowStockAlertService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StockMovementObserver implements ShouldHandleEventsAfterCommit
{
    public function created(StockMovement $movement): void
    {
        app(LowStockAlertService::class)->sync($movement->product_id, $movement->location_id);
    }
}
