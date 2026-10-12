<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\LowStockAlert;
use App\Services\BatchInventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private function stockQuery(Request $request): Builder
    {
        $locationId = $request->user()?->hasRole('pic_outlet')
            ? $request->user()->location_id
            : $request->session()->get('selected_location_id');
        $query = Product::query()->with(['category', 'unit'])
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->where('type', 'in')->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->where('type', 'out')->when($locationId, fn ($movement) => $movement->where('location_id', $locationId))], 'quantity');
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
        abort_unless(! $request->user()->hasRole('pic_outlet') || $request->user()->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        $products = $this->stockQuery($request)->paginate(15)->withQueryString();
        $products->getCollection()->each(fn (Product $product) => $product->current_stock = (float) $product->stock_in - (float) $product->stock_out);
        $lowStockByProduct = $request->user()->hasPermissionTo('low_stock.view')
            ? $this->lowStockLocationsByProduct($request)
            : [];

        return view('inventory.stock.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $request->only(['q', 'category', 'status', 'sort', 'direction']),
            'lowStockByProduct' => $lowStockByProduct,
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $lowStockByProduct = $request->user()->hasPermissionTo('low_stock.view') ? $this->lowStockLocationsByProduct($request) : [];
        $items = $this->stockQuery($request)->paginate(15)->getCollection()->map(fn (Product $product) => [
            'id' => $product->id,
            'quantity' => (float) $product->stock_in - (float) $product->stock_out,
            'unit' => $product->unit->symbol,
            'low_stock_locations' => $lowStockByProduct[$product->id] ?? [],
        ])->values();

        return response()->json(['items' => $items, 'updated_at' => now()->toIso8601String()]);
    }

    private function lowStockLocationsByProduct(Request $request): array
    {
        return LowStockAlert::active()->with('location:id,name')
            ->when($request->user()->hasRole('pic_outlet'), fn ($query) => $query->where('location_id', $request->user()->location_id))
            ->when(! $request->user()->hasRole('pic_outlet') && $request->session()->get('selected_location_id'), fn ($query) => $query->where('location_id', $request->session()->get('selected_location_id')))
            ->get()->groupBy('product_id')->map(fn ($alerts) => $alerts->pluck('location.name')->all())->all();
    }

    public function show(Request $request, Product $product, BatchInventoryService $batchInventory): View
    {
        abort_unless(! $request->user()->hasRole('pic_outlet') || $request->user()->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        $product->load(['category', 'unit']);
        $locationId = $request->user()->hasRole('pic_outlet') ? $request->user()->location_id : $request->session()->get('selected_location_id');
        $movementQuery = $product->stockMovements()->when($locationId, fn ($query) => $query->where('location_id', $locationId));
        $product->current_stock = (float) (clone $movementQuery)->where('type', 'in')->sum('quantity')
            - (float) (clone $movementQuery)->where('type', 'out')->sum('quantity');

        return view('inventory.stock.show', [
            'product' => $product,
            'movements' => (clone $movementQuery)->with(['location', 'user'])->latest()->limit(20)->get(),
            'batches' => $batchInventory->productBatches($product->id, $locationId),
        ]);
    }
}
