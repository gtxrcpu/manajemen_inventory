<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\LowStockAlert;
use App\Models\StockMovement;
use App\Services\BatchInventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private function stockQuery(Request $request): Builder
    {
        $user = $request->user();
        $isPic = $user?->isLocationScopedUser() ?? false;
        $locationIds = $isPic ? $user->assignedLocationIds() : [];
        if ($isPic) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locationId = $isPic ? null : $request->session()->get('selected_location_id');
        $query = Product::query()->with(['category', 'unit'])
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->where('type', 'in')->when($isPic, fn ($movement) => $movement->whereIn('location_id', $locationIds))->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->where('type', 'out')->when($isPic, fn ($movement) => $movement->whereIn('location_id', $locationIds))->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity');
        if ($user?->isOutletUser()) {
            $query = $this->whereEverReceivedByOutlet($query, $locationIds);
        }
        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortColumn = in_array($sort, ['name', 'sku', 'created_at'], true) ? $sort : 'name';

        return $query->orderBy($sortColumn, $direction);
    }

    public function index(Request $request): View
    {
        $products = $this->stockQuery($request)->paginate(15)->withQueryString();
        $products->getCollection()->each(fn (Product $product) => $product->current_stock = (float) $product->stock_in - (float) $product->stock_out);
        $lowStockByProduct = $request->user()->hasPermissionTo('low_stock.view')
            ? $this->lowStockLocationsByProduct($request)
            : [];
        $stockByProductLocation = $this->stockByProductLocation($request, $products->getCollection()->pluck('id')->all());

        return view('inventory.stock.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $request->only(['q', 'category', 'status', 'sort', 'direction']),
            'lowStockByProduct' => $lowStockByProduct,
            'stockByProductLocation' => $stockByProductLocation,
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $products = $this->stockQuery($request)->paginate(15)->getCollection();
        $lowStockByProduct = $request->user()->hasPermissionTo('low_stock.view') ? $this->lowStockLocationsByProduct($request) : [];
        $stockByProductLocation = $this->stockByProductLocation($request, $products->pluck('id')->all());
        $items = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'quantity' => (float) $product->stock_in - (float) $product->stock_out,
            'unit' => $product->unit->symbol,
            'low_stock_locations' => $lowStockByProduct[$product->id] ?? [],
            'stock_by_location' => $stockByProductLocation[$product->id] ?? [],
        ])->values();

        return response()->json(['items' => $items, 'updated_at' => now()->toIso8601String()]);
    }

    private function lowStockLocationsByProduct(Request $request): array
    {
        $locationIds = $request->user()->isLocationScopedUser() ? $request->user()->assignedLocationIds() : [];
        return LowStockAlert::active()->with('location:id,name')
            ->when($request->user()->isLocationScopedUser(), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->when(! $request->user()->isLocationScopedUser() && $request->session()->get('selected_location_id'), fn ($query) => $query->where('location_id', $request->session()->get('selected_location_id')))
            ->get()->groupBy('product_id')->map(fn ($alerts) => $alerts->pluck('location.name')->all())->all();
    }

    private function stockByProductLocation(Request $request, array $productIds): array
    {
        if ($productIds === []) return [];

        $user = $request->user();
        $isPic = $user->isLocationScopedUser();
        $locationIds = $isPic ? $user->assignedLocationIds() : [];
        $selectedLocationId = $isPic ? null : $request->session()->get('selected_location_id');
        $locations = Location::query()->where('is_active', true)
            ->when($isPic, fn ($query) => $query->whereIn('id', $locationIds))
            ->when($selectedLocationId, fn ($query) => $query->whereKey($selectedLocationId))
            ->orderBy('name')->get(['id', 'name'])->keyBy('id');
        if ($locations->isEmpty()) return [];

        $balances = DB::table('stock_movements')->whereIn('product_id', $productIds)->whereIn('location_id', $locations->keys())
            ->select('product_id', 'location_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
            ->groupBy('product_id', 'location_id')->get();
        $thresholds = DB::table('product_location_stock_thresholds')->whereIn('product_id', $productIds)
            ->whereIn('location_id', $locations->keys())->where('minimum_quantity', '>', 0)
            ->get(['product_id', 'location_id']);
        $trackedThresholds = $thresholds->mapWithKeys(fn ($row) => [$row->product_id.':'.$row->location_id => true]);
        $breakdown = [];

        foreach ($balances as $row) {
            $key = $row->product_id.':'.$row->location_id;
            $quantity = (float) $row->balance;
            if (abs($quantity) <= 0.0005 && ! isset($trackedThresholds[$key])) continue;
            $breakdown[$row->product_id][] = [
                'location_id' => (int) $row->location_id,
                'location_name' => $locations[$row->location_id]->name,
                'quantity' => $quantity,
            ];
        }

        foreach ($thresholds as $row) {
            $key = $row->product_id.':'.$row->location_id;
            if (collect($breakdown[$row->product_id] ?? [])->contains('location_id', (int) $row->location_id)) continue;
            $breakdown[$row->product_id][] = [
                'location_id' => (int) $row->location_id,
                'location_name' => $locations[$row->location_id]->name,
                'quantity' => 0.0,
            ];
        }

        foreach ($breakdown as &$rows) {
            usort($rows, fn (array $a, array $b) => strcasecmp($a['location_name'], $b['location_name']));
        }
        unset($rows);

        return $breakdown;
    }

    public function show(Request $request, Product $product, BatchInventoryService $batchInventory): View
    {
        if ($request->user()->isOutletUser()) {
            $locationIds = $request->user()->assignedOutletLocationIds();
            abort_unless($locationIds, 403, 'Akun outlet belum memiliki outlet aktif yang ditugaskan.');
            abort_unless($this->whereEverReceivedByOutlet(Product::query()->whereKey($product->id), $locationIds)->exists(), 404);
        }
        $product->load(['category', 'unit']);
        $isPic = $request->user()->isLocationScopedUser();
        $locationIds = $isPic ? $request->user()->assignedLocationIds() : [];
        if ($isPic) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locationId = $isPic ? null : $request->session()->get('selected_location_id');
        $movementQuery = $product->stockMovements()->when($isPic, fn ($query) => $query->whereIn('location_id', $locationIds))->when($locationId, fn ($query) => $query->where('location_id', $locationId));
        $summaryQuery = StockMovement::query()->where('product_id', $product->id)
            ->when($isPic, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId));
        $summary = $summaryQuery->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END), 0) as total_out")
            ->selectRaw('COUNT(*) as movement_count')->first();
        $product->current_stock = (float) $summary->total_in - (float) $summary->total_out;
        $scopeLocations = $isPic
            ? Location::query()->whereIn('id', $locationIds)->where('is_active', true)->orderBy('name')->pluck('name')
            : ($locationId ? Location::query()->whereKey($locationId)->pluck('name') : collect());
        $movements = (clone $movementQuery)->with([
            'location', 'user', 'stockInItem.stockIn', 'stockOutItem.stockOut',
            'stockOpnameItem.opname', 'stockAdjustment', 'stockDisposalItem.disposal',
        ])->latest('id')->limit(20)->get();

        return view('inventory.stock.show', [
            'product' => $product,
            'movements' => $movements,
            'totalIn' => (float) $summary->total_in,
            'totalOut' => (float) $summary->total_out,
            'movementCount' => (int) $summary->movement_count,
            'scopeLocations' => $scopeLocations,
            'batches' => $batchInventory->productBatches($product->id, $isPic ? $locationIds : $locationId),
        ]);
    }

    private function whereEverReceivedByOutlet(Builder $query, array $locationIds): Builder
    {
        return $query->whereExists(function ($receipt) use ($locationIds) {
            $receipt->selectRaw('1')
                ->from('stock_transfer_receipt_items as receipt_items')
                ->join('stock_transfer_items as transfer_items', 'transfer_items.id', '=', 'receipt_items.stock_transfer_item_id')
                ->join('stock_transfers as transfers', 'transfers.id', '=', 'transfer_items.stock_transfer_id')
                ->join('locations as source', 'source.id', '=', 'transfers.source_location_id')
                ->whereColumn('transfer_items.product_id', 'products.id')
                ->whereIn('transfers.destination_location_id', $locationIds)
                ->where('source.type', Location::TYPE_WAREHOUSE)
                ->where('receipt_items.quantity', '>', 0);
        });
    }
}
