<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockOpnameDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Stock Opname demo data is limited to the local environment.');

            return;
        }

        $reviewer = User::query()
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'purchasing'))
            ->first()
            ?? User::query()->whereHas('roles', fn ($roles) => $roles->where('name', 'super_admin'))->first();

        if (! $reviewer) {
            $this->command?->warn('No Purchasing or Super Admin user is available for demo review records.');

            return;
        }

        $now = now();
        $products = Product::query()->where('is_active', true)->orderBy('id')->get(['id']);

        foreach (Location::query()->where('is_active', true)->whereIn('type', [Location::TYPE_WAREHOUSE, Location::TYPE_OUTLET])->orderBy('id')->get() as $location) {
            $counterRole = $location->type === Location::TYPE_WAREHOUSE ? 'staff_gudang' : 'staff_outlet';
            $counter = User::query()
                ->where('location_id', $location->id)
                ->whereHas('roles', fn ($roles) => $roles->where('name', $counterRole))
                ->first();

            if (! $counter || (int) $counter->id === (int) $reviewer->id) {
                $this->command?->warn("Skipped demo Stock Opname for {$location->name}: no distinct assigned counter and reviewer were found.");

                continue;
            }

            $cutoffId = (int) (StockMovement::query()->where('location_id', $location->id)->max('id') ?? 0);
            $balances = StockMovement::query()
                ->where('location_id', $location->id)
                ->selectRaw("product_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
                ->groupBy('product_id')
                ->pluck('balance', 'product_id');

            $prefix = $location->type === Location::TYPE_WAREHOUSE ? 'WH' : 'OUTLET';
            $reference = "DEMO-SO-{$prefix}-001";
            $opname = StockOpname::query()->updateOrCreate(['reference' => $reference], [
                'period_type' => $location->type === Location::TYPE_WAREHOUSE ? 'weekly' : 'month_end',
                'status' => 'rejected',
                'location_id' => $location->id,
                'counted_by' => $counter->id,
                'reviewed_by' => $reviewer->id,
                'snapshot_at' => $now,
                'submitted_at' => $now,
                'movement_cutoff_id' => $cutoffId,
                'reviewed_at' => $now,
                'review_note' => 'DATA DEMO: hasil contoh ditolak agar tidak menerbitkan penyesuaian stok.',
                'notes' => 'DATA DEMO: sesi historis untuk memperagakan daftar dan rincian Stock Opname.',
            ]);

            $productIds = $products->pluck('id')->all();
            $opname->items()->whereNotIn('product_id', $productIds)->delete();

            foreach ($products as $index => $product) {
                $systemQuantity = round((float) ($balances->get($product->id) ?? 0), 3);
                $sampleDelta = match ($index) {
                    0 => $systemQuantity > 0 ? -min(1, $systemQuantity) : 0,
                    1 => 1,
                    default => 0,
                };
                $physicalQuantity = max(0, round($systemQuantity + $sampleDelta, 3));
                $variance = round($physicalQuantity - $systemQuantity, 3);

                $opname->items()->updateOrCreate(['product_id' => $product->id], [
                    'system_quantity' => $systemQuantity,
                    'system_quantity_at_count' => $systemQuantity,
                    'physical_quantity' => $physicalQuantity,
                    'variance' => $variance,
                    'adjustment_quantity' => 0,
                    'reason' => abs($variance) > 0.0005 ? 'Selisih simulasi untuk data demo; hasil sesi ditolak.' : null,
                ]);
            }
        }
    }
}
