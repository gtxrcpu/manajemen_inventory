<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    private function assertCanManage(?Location $location = null): void
    {
        $user = auth()->user();
        abort_unless($user && ! $user->hasRole('purchasing') && $user->hasPermissionTo('opname.manage'), 403);
        if ($location && $user->isLocationScopedUser()) {
            abort_unless(in_array((int) $location->id, $user->assignedLocationIds(), true), 403);
        }
    }

    private function assertCanApprove(Location $location): void
    {
        $user = auth()->user();
        abort_unless($user && ! $user->hasRole('purchasing') && $user->hasPermissionTo('opname.approve'), 403);

        $isGlobalApprover = $user->hasRole('super_admin') || $user->hasRole('management') || $user->hasRole('finance');
        $isWarehousePic = $user->hasRole('pic_warehouse')
            && $location->type === Location::TYPE_WAREHOUSE
            && in_array((int) $location->id, $user->assignedWarehouseLocationIds(), true);
        $isOutletPic = $user->hasRole('pic_outlet')
            && $location->type === Location::TYPE_OUTLET
            && in_array((int) $location->id, $user->assignedOutletLocationIds(), true);
        abort_unless($isGlobalApprover || $isWarehousePic || $isOutletPic, 403);
    }

    public function start(array $data): StockOpname
    {
        $this->assertCanManage();
        return DB::transaction(function () use ($data) {
            $location = Location::whereKey($data['location_id'])->lockForUpdate()->firstOrFail();
            $this->assertCanManage($location);
            if (StockOpname::where('location_id', $location->id)->whereIn('status', ['counting', 'pending_approval'])->exists()) {
                throw ValidationException::withMessages(['location_id' => 'Masih ada Stock Opname yang belum selesai di lokasi ini. Selesaikan dulu sebelum membuat sesi baru.']);
            }
            $snapshotAt = now();
            $balances = StockMovement::where('location_id', $location->id)
                ->selectRaw("product_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
                ->groupBy('product_id')->pluck('balance', 'product_id');
            $scope = $data['period_type'] === 'weekly' ? ($data['count_scope'] ?? 'full') : 'full';
            $productIds = collect($data['product_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $productsQuery = Product::where('is_active', true)->orderBy('id');
            if ($scope === 'partial') {
                if ($productIds->isEmpty()) {
                    throw ValidationException::withMessages(['product_ids' => 'Pilih minimal satu produk untuk cycle count mingguan.']);
                }
                $productsQuery->whereIn('id', $productIds);
            }
            $products = $productsQuery->get(['id']);
            if ($scope === 'partial' && $products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['product_ids' => 'Pilihan cycle count memuat produk yang tidak aktif atau tidak valid. Muat ulang halaman.']);
            }
            if ($products->isEmpty()) {
                throw ValidationException::withMessages(['location_id' => 'Tambahkan produk aktif sebelum memulai Stock Opname.']);
            }

            $opname = StockOpname::create([
                'reference' => 'SO-'.$snapshotAt->format('Ymd').'-'.Str::upper(Str::random(6)),
                'period_type' => $data['period_type'],
                'location_id' => $location->id,
                'counted_by' => auth()->id(),
                'snapshot_at' => $snapshotAt,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($products as $product) {
                $opname->items()->create(['product_id' => $product->id, 'system_quantity' => (float) ($balances[$product->id] ?? 0)]);
            }
            $this->auditLogger->record('stock_opname.started', $opname, [
                'reference' => $opname->reference,
                'location_id' => $location->id,
                'period_type' => $opname->period_type,
                'count_scope' => $scope,
                'product_count' => $products->count(),
            ]);
            return $opname;
        });
    }

    public function submit(StockOpname $opname, array $counts): StockOpname
    {
        $this->assertCanManage($opname->location()->firstOrFail());
        return DB::transaction(function () use ($opname, $counts) {
            $opname = StockOpname::whereKey($opname->id)->lockForUpdate()->firstOrFail();
            if ($opname->status !== 'counting') throw ValidationException::withMessages(['opname' => 'Stock Opname ini sudah dikirim atau dikunci.']);
            Location::whereKey($opname->location_id)->lockForUpdate()->firstOrFail();
            $items = $opname->items()->lockForUpdate()->get()->keyBy('id');
            if (count($counts) !== $items->count() || $items->keys()->diff(collect($counts)->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => 'Semua produk dalam snapshot harus dihitung. Muat ulang halaman jika daftar berubah.']);
            }
            $balancesAtCount = StockMovement::where('location_id', $opname->location_id)
                ->selectRaw("product_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
                ->groupBy('product_id')->pluck('balance', 'product_id');
            foreach ($items as $id => $item) {
                $physical = (float) $counts[$id]['physical_quantity'];
                $systemAtCount = (float) ($balancesAtCount[$item->product_id] ?? 0);
                $variance = $physical - $systemAtCount;
                $reason = trim((string) ($counts[$id]['reason'] ?? ''));
                if (abs($variance) > 0.0005 && $reason === '') {
                    throw ValidationException::withMessages(["items.{$id}.reason" => 'Alasan wajib diisi jika ada selisih.']);
                }
                $item->forceFill(['system_quantity_at_count' => $systemAtCount, 'physical_quantity' => $physical, 'variance' => $variance, 'reason' => $reason ?: null])->save();
            }
            $cutoffId = StockMovement::where('location_id', $opname->location_id)->max('id') ?? 0;
            $opname->forceFill(['status' => 'pending_approval', 'submitted_at' => now(), 'movement_cutoff_id' => $cutoffId])->save();
            $this->auditLogger->record('stock_opname.submitted', $opname, ['item_count' => $items->count()]);
            return $opname;
        });
    }

    public function cancel(StockOpname $opname, string $reason): StockOpname
    {
        $this->assertCanManage($opname->location()->firstOrFail());
        return DB::transaction(function () use ($opname, $reason) {
            $opname = StockOpname::whereKey($opname->id)->lockForUpdate()->firstOrFail();
            if ($opname->status !== 'counting') {
                throw ValidationException::withMessages(['opname' => 'Hanya sesi yang masih dihitung yang dapat dibatalkan.']);
            }
            Location::whereKey($opname->location_id)->lockForUpdate()->firstOrFail();
            if ((int) $opname->counted_by !== (int) auth()->id() && ! auth()->user()->hasRole('super_admin')) {
                throw ValidationException::withMessages(['opname' => 'Hanya penghitung sesi atau Super Admin yang dapat membatalkan sesi.']);
            }

            $reviewNote = 'Sesi dibatalkan: '.trim($reason);
            $opname->forceFill(['status' => 'rejected', 'review_note' => $reviewNote])->save();
            $this->auditLogger->record('stock_opname.cancelled', $opname, [
                'reference' => $opname->reference,
                'cancelled_by' => auth()->id(),
                'reason' => trim($reason),
                'movement_created' => false,
            ]);

            return $opname;
        });
    }

    public function approve(StockOpname $opname, ?string $reviewNote = null): StockOpname
    {
        $this->assertCanApprove($opname->location()->firstOrFail());
        return DB::transaction(function () use ($opname, $reviewNote) {
            $opname = StockOpname::whereKey($opname->id)->lockForUpdate()->firstOrFail();
            Location::whereKey($opname->location_id)->lockForUpdate()->firstOrFail();
            if ($opname->status !== 'pending_approval') throw ValidationException::withMessages(['opname' => 'Hanya Stock Opname yang menunggu persetujuan yang bisa disetujui.']);
            if ((int) $opname->counted_by === (int) auth()->id()) throw ValidationException::withMessages(['opname' => 'Petugas penghitung tidak dapat menyetujui hasil hitungnya sendiri.']);
            $items = $opname->items()->with('product')->lockForUpdate()->get();
            foreach ($items as $item) {
                $movements = StockMovement::where('product_id', $item->product_id)->where('location_id', $opname->location_id)->lockForUpdate()->get(['id', 'type', 'quantity']);
                $currentBalance = $movements->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
                $afterCount = $movements->filter(fn (StockMovement $movement) => $movement->id > $opname->movement_cutoff_id)
                    ->sum(fn (StockMovement $movement) => $movement->type === 'in' ? (float) $movement->quantity : -(float) $movement->quantity);
                $delta = (float) $item->physical_quantity + $afterCount - $currentBalance;
                $item->forceFill(['adjustment_quantity' => $delta])->save();
                if (abs($delta) > 0.0005) {
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'location_id' => $opname->location_id,
                        'user_id' => auth()->id(),
                        'type' => $delta > 0 ? 'in' : 'out',
                        'quantity' => abs($delta),
                        'note' => 'Penyesuaian Stock Opname '.$opname->reference,
                        'stock_opname_item_id' => $item->id,
                    ]);
                }
            }
            $opname->forceFill(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $reviewNote])->save();
            $this->auditLogger->record('stock_opname.approved', $opname, [
                'reference' => $opname->reference,
                'review_note' => $reviewNote,
                'adjustments' => $items->map(fn (StockOpnameItem $item) => ['product_id' => $item->product_id, 'quantity' => $item->adjustment_quantity])->all(),
            ]);
            return $opname;
        });
    }

    public function reject(StockOpname $opname, string $reviewNote): StockOpname
    {
        $this->assertCanApprove($opname->location()->firstOrFail());
        return DB::transaction(function () use ($opname, $reviewNote) {
            $opname = StockOpname::whereKey($opname->id)->lockForUpdate()->firstOrFail();
            if ($opname->status !== 'pending_approval') throw ValidationException::withMessages(['opname' => 'Hanya Stock Opname yang menunggu persetujuan yang bisa ditolak.']);
            if ((int) $opname->counted_by === (int) auth()->id()) throw ValidationException::withMessages(['opname' => 'Petugas penghitung tidak dapat meninjau hasil hitungnya sendiri.']);
            $opname->forceFill(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $reviewNote])->save();
            $this->auditLogger->record('stock_opname.rejected', $opname, ['reference' => $opname->reference, 'review_note' => $reviewNote]);
            return $opname;
        });
    }
}
