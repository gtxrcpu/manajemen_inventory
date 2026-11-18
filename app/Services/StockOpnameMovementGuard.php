<?php

namespace App\Services;

use App\Models\StockOpname;
use Illuminate\Validation\ValidationException;

class StockOpnameMovementGuard
{
    public static function assertProductCanMove(int $locationId, int $productId): void
    {
        $isBeingCounted = StockOpname::query()
            ->where('location_id', $locationId)
            ->where('status', 'counting')
            ->whereHas('items', fn ($query) => $query->where('product_id', $productId))
            ->exists();

        if ($isBeingCounted) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk ini sedang dihitung dalam Stock Opname. Kirim hasil hitung terlebih dahulu sebelum mencatat pergerakan stok.',
            ]);
        }
    }
}
