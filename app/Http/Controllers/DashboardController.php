<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;
use App\Services\LowStockAlertService;
use App\Services\ManagementReportDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function stockItems(?User $user = null)
    {
        $locationId = $user?->isLocationScopedUser() ? ($user->assignedLocationIds()[0] ?? null) : session('selected_location_id');
        $balances = DB::table('stock_movements')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as current_stock")
            ->groupBy('product_id');

        return Product::query()->where('products.is_active', true)->with(['category', 'unit'])
            ->leftJoinSub($balances, 'stock_balances', fn ($join) => $join->on('stock_balances.product_id', '=', 'products.id'))
            ->select('products.*')->selectRaw('COALESCE(stock_balances.current_stock, 0) as current_stock')
            ->orderBy('products.name')->get();
    }

    private function liveLowStockCount(?User $user = null): int
    {
        $locationId = $user?->isLocationScopedUser()
            ? ($user->assignedLocationIds()[0] ?? null)
            : session('selected_location_id');
        $balances = DB::table('stock_movements')
            ->select('product_id', 'location_id')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
            ->groupBy('product_id', 'location_id');

        return DB::table('product_location_stock_thresholds as thresholds')
            ->join('products', 'products.id', '=', 'thresholds.product_id')
            ->leftJoinSub($balances, 'balances', fn ($join) => $join
                ->on('balances.product_id', '=', 'thresholds.product_id')
                ->on('balances.location_id', '=', 'thresholds.location_id'))
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->where('thresholds.minimum_quantity', '>', 0)
            ->when($locationId, fn ($query) => $query->where('thresholds.location_id', $locationId))
            ->whereRaw('COALESCE(balances.balance, 0) <= thresholds.minimum_quantity')
            ->count();
    }

    private function expiringBatchCount(?User $user = null): int
    {
        $locationId = $user?->isLocationScopedUser()
            ? ($user->assignedLocationIds()[0] ?? null)
            : session('selected_location_id');
        $cutoff = now('Asia/Jakarta')->addDays(30)->toDateString();
        $atRiskBatches = DB::table('stock_in_items as batches')
            ->join('stock_ins as receipts', 'receipts.id', '=', 'batches.stock_in_id')
            ->join('stock_movements as movements', 'movements.batch_id', '=', 'batches.id')
            ->whereNotNull('batches.expiry_date')
            ->whereDate('batches.expiry_date', '<=', $cutoff)
            ->when($locationId, fn ($query) => $query->where('receipts.location_id', $locationId))
            ->select('batches.id')
            ->groupBy('batches.id')
            ->havingRaw("SUM(CASE WHEN movements.type = 'in' THEN movements.quantity WHEN movements.type = 'out' THEN -movements.quantity ELSE 0 END) > 0.0005");

        return DB::query()->fromSub($atRiskBatches, 'at_risk_batches')->count();
    }

    private function stockMovementTrend(?User $user = null): array
    {
        $locationId = $user?->isLocationScopedUser()
            ? ($user->assignedLocationIds()[0] ?? null)
            : session('selected_location_id');
        $today = now('Asia/Jakarta')->startOfDay();
        $startUtc = $today->copy()->subDays(6)->utc();
        $endUtc = $today->copy()->endOfDay()->utc();
        $counts = DB::table('stock_movements')
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->selectRaw("DATE(DATE_ADD(created_at, INTERVAL 7 HOUR)) as movement_date, type, COUNT(*) as total")
            ->groupBy('movement_date', 'type')
            ->get()
            ->keyBy(fn ($row) => $row->movement_date.':'.$row->type);

        $days = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $today->copy()->subDays($offset);
            $key = $date->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $date->locale('id')->translatedFormat('d M'),
                'incoming' => (int) ($counts[$key.':in']->total ?? 0),
                'outgoing' => (int) ($counts[$key.':out']->total ?? 0),
            ];
        }

        return $days;
    }

    public function stockLive()
    {
        $user = auth()->user();
        abort_unless(! $user->isLocationScopedUser() || $user->assignedLocationIds(), 403, 'Akun operasional belum ditetapkan ke lokasi aktif.');
        return response()->json([
            'items' => $this->stockItems($user)->map(fn (Product $p) => ['id' => $p->id, 'sku' => $p->sku, 'name' => $p->name, 'image' => $p->image_path ? asset('storage/'.$p->image_path) : null, 'category' => $p->category->name, 'unit' => $p->unit->symbol, 'quantity' => $p->current_stock]),
            'low_stock_alert_count' => $this->liveLowStockCount($user),
            'expiring_batch_count' => $this->expiringBatchCount($user),
            'stock_movement_trend' => $this->stockMovementTrend($user),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function __invoke(LowStockAlertService $lowStock, Request $request, ManagementReportDashboardService $managementDashboard): View
    {
        $user = auth()->user();
        abort_unless(! $user->isLocationScopedUser() || $user->assignedLocationIds() || app()->runningUnitTests(), 403, 'Akun operasional belum ditetapkan ke lokasi aktif.');

        if ($user->hasRole('management') && ! $user->hasRole('super_admin')) {
            abort_unless($user->hasPermissionTo('reports.view'), 403, 'Akun Management belum memiliki akses laporan.');
            $filters = $request->validate([
                'period' => ['nullable', 'in:7,30,90'],
            ]);

            $locationId = $request->session()->get('selected_location_id');
            if ($locationId && ! Location::query()->whereKey($locationId)->where('is_active', true)->exists()) {
                $request->session()->forget('selected_location_id');
                $locationId = null;
            }

            return view('management.dashboard', $managementDashboard->build(
                $user,
                $locationId ? (int) $locationId : null,
                (int) ($filters['period'] ?? 30),
            ));
        }

        $dashboardRoles = $user->roles()->pluck('name')->all();
        $selectedLocationId = $user->isLocationScopedUser() ? ($user->assignedLocationIds()[0] ?? null) : session('selected_location_id');
        $roleActions = [
            'staff_gudang' => [['label' => 'Validasi Penerimaan', 'icon' => 'fact_check'], ['label' => 'Barang Keluar', 'icon' => 'remove']],
            'staff_outlet' => [['label' => 'Stok Outlet', 'icon' => 'inventory_2'], ['label' => 'Terima Transfer', 'icon' => 'move_to_inbox']],
            'pic_outlet' => [['label' => 'Stok Outlet', 'icon' => 'inventory_2'], ['label' => 'Terima Barang', 'icon' => 'move_to_inbox']],
            'purchasing' => [['label' => 'Pantau Gudang', 'icon' => 'visibility'], ['label' => 'Riwayat Opname', 'icon' => 'fact_check']],
            'finance' => [
                ['label' => 'Stok Seluruh Gudang', 'icon' => 'warehouse'],
                ['label' => 'Valuasi Persediaan', 'icon' => 'calculate'],
                ['label' => 'Faktur Pembelian', 'icon' => 'receipt_long', 'url' => route('purchase-invoices.index')],
            ],
        ];
        $dashboardActions = collect($dashboardRoles)
            ->flatMap(fn (string $role) => $roleActions[$role] ?? [])
            ->unique('label')
            ->values();
        $lowStockAlerts = $user->hasPermissionTo('low_stock.view')
            ? $lowStock->activeForUser($user)->when($selectedLocationId, fn ($query) => $query->where('location_id', $selectedLocationId))->limit(5)->get()
            : collect();
        $lowStockAlertCount = $this->liveLowStockCount($user);

        return view('dashboard', [
            'activeProductCount' => Product::where('is_active', true)->count(),
            'activeLocationCount' => $selectedLocationId ? Location::whereKey($selectedLocationId)->where('is_active', true)->count() : Location::where('is_active', true)->count(),
            'activeUserCount' => User::where('is_active', true)->count(),
            'roleCount' => Role::count(),
            'isSuperAdmin' => $user->hasRole('super_admin'),
            'dashboardRoles' => $dashboardRoles,
            'dashboardActions' => $dashboardActions,
            // Keep demo usage/waste charts hidden until reliable movement aggregates are available.
            'inventorySummaryAvailable' => false,
            'expiringBatchCount' => $this->expiringBatchCount($user),
            'stockMovementTrend' => $this->stockMovementTrend($user),
            'stockItems' => $this->stockItems($user),
            'stockCategories' => Category::query()->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->where('is_active', true))
                ->orderBy('name')->get(['id', 'name']),
            'stockMovementCount' => StockMovement::when($selectedLocationId, fn ($q) => $q->where('location_id', $selectedLocationId))->count(),
            'lowStockAlerts' => $lowStockAlerts,
            'lowStockAlertCount' => $lowStockAlertCount,
            'canViewLowStock' => $user->hasPermissionTo('low_stock.view'),
            'whatsAppAlertsEnabled' => $user->hasPermissionTo('whatsapp.manage') && (bool) WhatsAppAlertSetting::query()->value('enabled'),
        ]);
    }
}
