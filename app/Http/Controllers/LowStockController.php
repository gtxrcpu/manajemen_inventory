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
        abort_unless(! $user->hasRole('pic_outlet') || $user->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        $user->unreadNotifications()->where('type', LowStockNotification::class)->get()->markAsRead();
        $locationId = $user->hasRole('pic_outlet') ? $user->location_id : $request->session()->get('selected_location_id');
        $scope = fn ($query) => $query->when($locationId, fn ($alerts) => $alerts->where('location_id', $locationId));

        return view('low-stock.index', [
            'activeAlerts' => $scope(LowStockAlert::active()->with(['product.unit', 'location']))->latest('triggered_at')->paginate(15, ['*'], 'active'),
            'history' => $scope(LowStockAlert::with(['product.unit', 'location']))->latest('triggered_at')->paginate(15, ['*'], 'history'),
            'canManage' => $user->hasPermissionTo('low_stock.manage'),
        ]);
    }

    public function thresholds(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('low_stock.manage'), 403);
        $request->validate(['location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')]]);
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $locationId = $request->integer('location_id') ?: $request->session()->get('selected_location_id') ?: $locations->first()?->id;
        $products = Product::with(['category', 'unit'])->where('is_active', true)->orderBy('name')->paginate(50)->withQueryString();
        $thresholds = $locationId
            ? DB::table('product_location_stock_thresholds')->where('location_id', $locationId)->whereIn('product_id', $products->getCollection()->pluck('id'))->pluck('minimum_quantity', 'product_id')
            : collect();
        return view('low-stock.thresholds', compact('locations', 'locationId', 'products', 'thresholds'));
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
        foreach (array_keys($data['thresholds']) as $productId) {
            abort_unless(Product::whereKey($productId)->where('is_active', true)->exists(), 422, 'Daftar produk berubah. Muat ulang halaman lalu simpan lagi.');
        }
        $service->saveThresholds((int) $data['location_id'], $data['thresholds']);
        return redirect()->route('low-stock.thresholds', ['location_id' => $data['location_id'], 'page' => $data['page'] ?? 1])
            ->with('status', 'Batas minimum stok berhasil disimpan dan alert diperbarui.');
    }
}
