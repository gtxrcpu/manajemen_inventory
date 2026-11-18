<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    private function assertPicLocation($user, int $locationId): void
    {
        if ($user->isLocationScopedUser()) {
            abort_unless(in_array($locationId, $user->assignedLocationIds(), true), 403, 'Akun operasional hanya dapat mengakses Stock Opname di lokasi penugasannya.');
        }
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermissionTo('opname.manage') || $user->hasPermissionTo('opname.approve'), 403);
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        if ($user->isLocationScopedUser()) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');

        $query = StockOpname::with(['location', 'counter', 'reviewer', 'items']);
        $statsQuery = StockOpname::query();
        $locationId = $request->integer('location_id') ?: null;
        if ($user->isLocationScopedUser()) {
            if ($locationId) abort_unless(in_array($locationId, $locationIds, true), 403);
            $scope = $locationId ? [$locationId] : $locationIds;
            $query->whereIn('location_id', $scope);
            $statsQuery->whereIn('location_id', $scope);
        } elseif ($locationId) {
            $query->where('location_id', $locationId);
            $statsQuery->where('location_id', $locationId);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where('reference', 'like', "%{$q}%");
        }
        $status = $request->query('status');
        if (in_array($status, ['counting', 'pending_approval', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        $stats = [
            'total'           => (clone $statsQuery)->count(),
            'counting'        => (clone $statsQuery)->where('status', 'counting')->count(),
            'pending_approval'=> (clone $statsQuery)->where('status', 'pending_approval')->count(),
            'approved'        => (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected'        => (clone $statsQuery)->where('status', 'rejected')->count(),
        ];

        return view('inventory.stock-opname.index', [
            'opnames'   => $query->latest()->paginate(15)->withQueryString(),
            'locations' => Location::where('is_active', true)->when($user->isLocationScopedUser(), fn ($query) => $query->whereIn('id', $locationIds))->orderBy('name')->get(),
            'stats'     => $stats,
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('opname.manage'), 403);
        $locationScoped = $user->isLocationScopedUser();
        $locationIds = $locationScoped ? $user->assignedLocationIds() : [];
        if ($locationScoped) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locations = Location::where('is_active', true)->when($locationScoped, fn ($query) => $query->whereIn('id', $locationIds))->orderBy('name')->get();
        $selectedLocationId = $locationScoped
            ? (in_array((int) $user->location_id, $locationIds, true) ? (int) $user->location_id : $locationIds[0])
            : null;
        return view('inventory.stock-opname.create', [
            'locations' => $locations,
            'locationScoped' => $locationScoped,
            'selectedLocation' => $locationScoped ? $locations->firstWhere('id', $selectedLocationId) : null,
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']),
        ]);
    }

    public function start(Request $request, StockOpnameService $service): RedirectResponse
    {
        $user = $request->user();
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        if ($user->isLocationScopedUser()) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $fixedLocationId = $user->isLocationScopedUser()
            ? (in_array((int) $user->location_id, $locationIds, true) ? (int) $user->location_id : $locationIds[0])
            : null;
        $locationRules = ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'];
        if ($fixedLocationId) $locationRules[] = Rule::in([$fixedLocationId]);
        $data = $request->validate([
            'location_id' => $locationRules,
            'period_type' => ['required', Rule::in(['weekly', 'month_end'])],
            'count_scope' => ['nullable', Rule::in(['full', 'partial'])],
            'product_ids' => ['nullable', 'array', 'min:1', Rule::requiredIf($request->input('period_type') === 'weekly' && $request->input('count_scope') === 'partial')],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id,is_active,1,deleted_at,NULL'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertPicLocation($user, (int) $data['location_id']);
        if ($data['period_type'] === 'month_end') {
            $data['count_scope'] = 'full';
            unset($data['product_ids']);
        }
        $opname = $service->start($data);
        return redirect()->route('inventory.stock-opname.show', $opname)->with('status', 'Snapshot saldo berhasil dibuat. Lakukan penghitungan fisik untuk setiap produk.');
    }

    public function show(StockOpname $stockOpname): View
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('opname.manage') || $user->hasPermissionTo('opname.approve'), 403);
        $this->assertPicLocation($user, (int) $stockOpname->location_id);
        return view('inventory.stock-opname.show', ['opname' => $stockOpname->load(['location', 'counter', 'reviewer', 'items.product.unit'])]);
    }

    public function submit(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $this->assertPicLocation($request->user(), (int) $stockOpname->location_id);
        $items = $stockOpname->items()->get(['id']);
        $rules = ['items' => ['required', 'array', 'size:'.$items->count()]];
        foreach ($items as $item) {
            $rules["items.{$item->id}.physical_quantity"] = ['required', 'numeric', 'min:0', 'max:999999999'];
            $rules["items.{$item->id}.reason"] = ['nullable', 'string', 'max:500'];
        }
        $data = Validator::make($request->all(), $rules, [
            'items.size' => 'Semua produk harus dicatat. Muat ulang halaman jika item tidak lengkap.',
            '*.required' => 'Jumlah fisik wajib diisi.',
            '*.min' => 'Jumlah fisik tidak boleh kurang dari nol.',
        ])->validate();
        $service->submit($stockOpname, $data['items']);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Hasil hitung terkirim ke Purchasing untuk ditinjau.');
    }

    public function approve(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $this->assertPicLocation($request->user(), (int) $stockOpname->location_id);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:5000']]);
        $service->approve($stockOpname, $data['review_note'] ?? null);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Stock Opname disetujui dan saldo telah disesuaikan.');
    }

    public function cancel(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $this->assertPicLocation($request->user(), (int) $stockOpname->location_id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:4500']]);
        $service->cancel($stockOpname, $data['reason']);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Sesi dibatalkan tanpa mengubah saldo. Produk dapat diproses kembali.');
    }

    public function reject(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $this->assertPicLocation($request->user(), (int) $stockOpname->location_id);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:5000']]);
        $service->reject($stockOpname, $data['review_note']);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Stock Opname ditolak. Buat opname baru jika perlu menghitung ulang.');
    }
}
