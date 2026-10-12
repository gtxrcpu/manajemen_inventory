<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BatchInventoryService
{
    /** Returns available and expired batches plus any legacy/untracked balance. */
    public function stockOutOptions(): array
    {
        $totals = DB::table('stock_movements')
            ->selectRaw("product_id, location_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
            ->groupBy('product_id', 'location_id')->get()->keyBy(fn ($row) => $row->product_id.':'.$row->location_id);

        $batches = DB::table('stock_movements as movements')
            ->join('stock_in_items as batches', 'batches.id', '=', 'movements.batch_id')
            ->join('stock_ins as receipts', 'receipts.id', '=', 'batches.stock_in_id')
            ->whereNotNull('movements.batch_id')
            ->selectRaw("batches.id, movements.product_id, movements.location_id, batches.batch_number, batches.expiry_date, COALESCE(batches.batch_received_at, receipts.received_at) as received_at, SUM(CASE WHEN movements.type = 'in' THEN movements.quantity ELSE -movements.quantity END) as balance")
            ->groupBy('batches.id', 'movements.product_id', 'movements.location_id', 'batches.batch_number', 'batches.expiry_date', 'batches.batch_received_at', 'receipts.received_at')
            ->get();

        $tracked = $batches->groupBy(fn ($batch) => $batch->product_id.':'.$batch->location_id)
            ->map(fn ($items) => (float) $items->sum('balance'));
        $options = [];
        foreach ($batches as $batch) {
            if ((float) $batch->balance <= 0) continue;
            $key = $batch->product_id.':'.$batch->location_id;
            $expiry = $batch->expiry_date ? \Illuminate\Support\Carbon::parse($batch->expiry_date)->startOfDay() : null;
            $receivedAt = \Illuminate\Support\Carbon::parse($batch->received_at)->startOfDay();
            $ageDays = max(0, (int) $receivedAt->diffInDays(today()));
            $options[$batch->product_id][$batch->location_id][] = [
                'id' => (string) $batch->id,
                'label' => ($batch->batch_number ?: 'Batch tanpa nomor').' · sisa '.\App\Support\QuantityFormatter::format((float) $batch->balance).($expiry ? ' · exp '.$expiry->format('d/m/Y') : ' · masuk '.$receivedAt->format('d/m/Y').' · '.$ageDays.' hari'),
                'quantity' => (float) $batch->balance,
                'expiry_date' => $expiry?->toDateString(),
                'received_at' => $batch->received_at,
                'age_days' => $ageDays,
                'expired' => $expiry?->isBefore(today()) ?? false,
                'near_expiry' => $expiry && ! $expiry->isBefore(today()) && $expiry->lessThanOrEqualTo(today()->addDays(30)),
            ];
        }

        foreach ($totals as $key => $total) {
            $untracked = (float) $total->balance - (float) ($tracked[$key] ?? 0);
            if ($untracked > 0.0005) {
                [$productId, $locationId] = explode(':', $key);
                $options[$productId][$locationId][] = [
                    'id' => 'untracked',
                    'label' => 'Stok tanpa batch tercatat · sisa '.number_format($untracked, 3, ',', '.'),
                    'quantity' => $untracked,
                    'expiry_date' => null,
                    'received_at' => null,
                    'expired' => false,
                    'near_expiry' => false,
                ];
            }
        }

        foreach ($options as &$locations) {
            foreach ($locations as &$choices) {
                usort($choices, fn (array $a, array $b) => [
                    $a['expiry_date'] === null ? 1 : 0,
                    $a['expiry_date'] ?? '9999-12-31',
                    $a['received_at'] ?? '9999-12-31',
                    $a['id'] === 'untracked' ? 1 : 0,
                    is_numeric($a['id']) ? (int) $a['id'] : PHP_INT_MAX,
                ] <=> [
                    $b['expiry_date'] === null ? 1 : 0,
                    $b['expiry_date'] ?? '9999-12-31',
                    $b['received_at'] ?? '9999-12-31',
                    $b['id'] === 'untracked' ? 1 : 0,
                    is_numeric($b['id']) ? (int) $b['id'] : PHP_INT_MAX,
                ]);
            }
            unset($choices);
        }
        unset($locations);

        return $options;
    }

    public function productBatches(int $productId, ?int $locationId = null): \Illuminate\Support\Collection
    {
        return DB::table('stock_in_items as batches')
            ->join('stock_ins as receipts', 'receipts.id', '=', 'batches.stock_in_id')
            ->join('locations', 'locations.id', '=', 'receipts.location_id')
            ->leftJoin('stock_movements as movements', 'movements.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productId)
            ->when($locationId, fn ($query) => $query->where('receipts.location_id', $locationId))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('stock_movements as tracked')->whereColumn('tracked.batch_id', 'batches.id'))
            ->selectRaw("batches.id, batches.batch_number, batches.expiry_date, COALESCE(batches.batch_received_at, receipts.received_at) as received_at, receipts.reference, receipts.location_id, locations.name as location_name, COALESCE(SUM(CASE WHEN movements.type = 'in' THEN movements.quantity WHEN movements.type = 'out' THEN -movements.quantity ELSE 0 END), 0) as remaining")
            ->groupBy('batches.id', 'batches.batch_number', 'batches.expiry_date', 'batches.batch_received_at', 'receipts.received_at', 'receipts.reference', 'receipts.location_id', 'locations.name')
            ->orderByRaw('batches.expiry_date IS NULL')
            ->orderBy('batches.expiry_date')
            ->get();
    }
}
