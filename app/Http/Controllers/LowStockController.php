<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\LowStockAlert;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use App\Services\LowStockAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LowStockController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermissionTo('low_stock.view'), 403);
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        if ($user->isLocationScopedUser()) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $user->unreadNotifications()->where('type', LowStockNotification::class)->get()->markAsRead();
        $locationId = $user->isLocationScopedUser() ? null : $request->session()->get('selected_location_id');
        $scope = fn ($query) => $user->isLocationScopedUser()
            ? $query->whereIn('location_id', $locationIds)
            : $query->when($locationId, fn ($alerts) => $alerts->where('location_id', $locationId));

        return view('low-stock.index', [
            'activeAlerts' => $scope(LowStockAlert::active()->with(['product.unit', 'location']))->latest('triggered_at')->paginate(15, ['*'], 'active'),
            'history' => $scope(LowStockAlert::with(['product.unit', 'location']))->latest('triggered_at')->paginate(15, ['*'], 'history'),
            'resolvedAlertCount' => $scope(LowStockAlert::whereNotNull('resolved_at'))->count(),
            'canManage' => $user->hasPermissionTo('low_stock.manage'),
        ]);
    }

    public function thresholds(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('low_stock.manage'), 403);
        $request->validate(['location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')]]);
        $user = $request->user();
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        if ($user->isLocationScopedUser()) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locations = Location::where('is_active', true)->when($user->isLocationScopedUser(), fn ($query) => $query->whereIn('id', $locationIds))->orderBy('name')->get();
        $locationId = $request->integer('location_id') ?: $request->session()->get('selected_location_id') ?: $locations->first()?->id;
        if ($user->isLocationScopedUser()) abort_unless(in_array((int) $locationId, $locationIds, true), 403);
        $products = Product::with(['category', 'unit'])->where('is_active', true)->orderBy('name')->paginate(50)->withQueryString();
        $thresholds = $locationId
            ? DB::table('product_location_stock_thresholds')->where('location_id', $locationId)->whereIn('product_id', $products->getCollection()->pluck('id'))->pluck('minimum_quantity', 'product_id')
            : collect();
        $stockBalances = $locationId
            ? DB::table('stock_movements')->where('location_id', $locationId)->whereIn('product_id', $products->getCollection()->pluck('id'))
                ->select('product_id')->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
                ->groupBy('product_id')->pluck('balance', 'product_id')
            : collect();
        $alertStates = $products->getCollection()->mapWithKeys(function (Product $product) use ($thresholds, $stockBalances) {
            $minimum = (float) ($thresholds[$product->id] ?? 0);
            $balance = (float) ($stockBalances[$product->id] ?? 0);

            return [$product->id => [
                'balance' => $balance,
                'state' => $minimum <= 0 ? 'Nonaktif' : ($balance <= $minimum + 0.0000005 ? 'Stok rendah' : 'Aman'),
            ]];
        });
        return view('low-stock.thresholds', compact('locations', 'locationId', 'products', 'thresholds', 'stockBalances', 'alertStates'));
    }

    public function saveThresholds(Request $request, LowStockAlertService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('low_stock.manage'), 403);
        $data = $request->validate([
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'page' => ['nullable', 'integer', 'min:1'],
            'thresholds' => ['required', 'array', 'min:1'],
            'thresholds.*' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);
        if ($request->user()->isWarehouseStaff()) {
            abort_unless(in_array((int) $data['location_id'], $request->user()->assignedWarehouseLocationIds(), true), 403);
        } elseif ($request->user()->isOutletUser()) {
            abort_unless($request->user()->canAccessOutletLocation((int) $data['location_id']), 403);
        }
        foreach (array_keys($data['thresholds']) as $productId) {
            abort_unless(Product::whereKey($productId)->where('is_active', true)->exists(), 422, 'Daftar produk berubah. Muat ulang halaman lalu simpan lagi.');
        }
        $service->saveThresholds((int) $data['location_id'], $data['thresholds']);
        return redirect()->route('low-stock.thresholds', ['location_id' => $data['location_id'], 'page' => $data['page'] ?? 1])
            ->with('status', 'Batas minimum stok berhasil disimpan dan alert diperbarui.');
    }
}
