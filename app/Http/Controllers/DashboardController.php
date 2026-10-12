<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;
use App\Services\LowStockAlertService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function stockItems(?User $user = null)
    {
        $locationId = $user?->hasRole('pic_outlet') ? $user->location_id : session('selected_location_id');
        return Product::query()->where('is_active', true)->with(['category', 'unit'])
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->where('type', 'in')->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->where('type', 'out')->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity')
            ->orderBy('name')->get()->map(function (Product $product) {
                $product->current_stock = (float) $product->stock_in - (float) $product->stock_out;
                return $product;
            });
    }

    public function stockLive(LowStockAlertService $lowStock)
    {
        $user = auth()->user();
        abort_unless(! $user->hasRole('pic_outlet') || $user->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        return response()->json([
            'items' => $this->stockItems($user)->map(fn (Product $p) => ['id' => $p->id, 'sku' => $p->sku, 'name' => $p->name, 'image' => $p->image_path ? asset('storage/'.$p->image_path) : null, 'category' => $p->category->name, 'unit' => $p->unit->symbol, 'quantity' => $p->current_stock]),
            'low_stock_alert_count' => $user->hasPermissionTo('low_stock.view') ? $lowStock->activeForUser($user)->count() : null,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function __invoke(LowStockAlertService $lowStock): View
    {
        $user = auth()->user();
        abort_unless(! $user->hasRole('pic_outlet') || $user->location_id || app()->runningUnitTests(), 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');

        $dashboardRoles = $user->roles()->pluck('name')->all();
        $selectedLocationId = $user->hasRole('pic_outlet') ? $user->location_id : session('selected_location_id');
        $roleActions = [
            'staff_gudang' => [['label' => 'Barang Masuk', 'icon' => 'add'], ['label' => 'Barang Keluar', 'icon' => 'remove']],
            'pic_outlet' => [['label' => 'Stok Outlet', 'icon' => 'inventory_2'], ['label' => 'Terima Barang', 'icon' => 'move_to_inbox']],
            'purchasing' => [['label' => 'Pantau Gudang', 'icon' => 'visibility'], ['label' => 'Review Opname', 'icon' => 'fact_check']],
            'finance' => [['label' => 'Stok Seluruh Gudang', 'icon' => 'warehouse'], ['label' => 'Valuasi Persediaan', 'icon' => 'calculate']],
        ];
        $dashboardActions = collect($dashboardRoles)
            ->flatMap(fn (string $role) => $roleActions[$role] ?? [])
            ->unique('label')
            ->values();
        $lowStockAlerts = $user->hasPermissionTo('low_stock.view')
            ? $lowStock->activeForUser($user)->when($selectedLocationId, fn ($query) => $query->where('location_id', $selectedLocationId))->limit(5)->get()
            : collect();

        return view('dashboard', [
            'activeProductCount' => Product::where('is_active', true)->count(),
            'activeLocationCount' => $selectedLocationId ? Location::whereKey($selectedLocationId)->where('is_active', true)->count() : Location::where('is_active', true)->count(),
            'activeUserCount' => User::where('is_active', true)->count(),
            'roleCount' => Role::count(),
            'isSuperAdmin' => $user->hasRole('super_admin'),
            'dashboardRoles' => $dashboardRoles,
            'dashboardActions' => $dashboardActions,
            // Legacy FEFO demo widgets remain hidden until batch/expiry data exists.
            'inventorySummaryAvailable' => false,
            'stockItems' => $this->stockItems($user),
            'stockMovementCount' => StockMovement::when($selectedLocationId, fn ($q) => $q->where('location_id', $selectedLocationId))->count(),
            'lowStockAlerts' => $lowStockAlerts,
            'lowStockAlertCount' => $user->hasPermissionTo('low_stock.view') ? $lowStock->activeForUser($user)->when($selectedLocationId, fn ($query) => $query->where('location_id', $selectedLocationId))->count() : 0,
            'canViewLowStock' => $user->hasPermissionTo('low_stock.view'),
            'whatsAppAlertsEnabled' => $user->hasPermissionTo('whatsapp.manage') && (bool) WhatsAppAlertSetting::query()->value('enabled'),
        ]);
    }
}
