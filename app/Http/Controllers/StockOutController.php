<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockOut;
use App\Services\BatchInventoryService;
use App\Services\StockOutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockOutController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $locationId = $user->hasRole('pic_outlet')
            ? $user->location_id
            : ($request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id'));
        $query = StockOut::with(['location', 'issuer', 'items.product.unit', 'items.batch']);

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('destination', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
            });
        }

        if ($locationId) $query->where('location_id', $locationId);

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->input('date_to'));
        }

        $issues = $query->latest('issued_at')->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total_issues' => StockOut::when($locationId, fn ($q) => $q->where('location_id', $locationId))->count(),
            'total_items_qty' => (float) \App\Models\StockOutItem::whereHas('stockOut', fn ($q) => $q->when($locationId, fn ($sub) => $sub->where('location_id', $locationId)))->sum('quantity'),
            'this_month_count' => StockOut::whereMonth('issued_at', now()->month)->whereYear('issued_at', now()->year)->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count(),
            'locations_count' => $locationId ? Location::whereKey($locationId)->where('is_active', true)->count() : Location::where('is_active', true)->count(),
        ];

        return view('inventory.stock-out.index', [
            'issues' => $issues,
            'stats' => $stats,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'filters' => array_merge($request->only(['q', 'date_from', 'date_to']), ['location_id' => $locationId]),
        ]);
    }

    public function create(Request $request, BatchInventoryService $batchInventory): View
    {
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        return view('inventory.stock-out.create', [
            'products' => $products,
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'selectedLocationId' => $request->user()->hasRole('pic_outlet') ? $request->user()->location_id : $request->session()->get('selected_location_id'),
            'batchOptions' => $batchInventory->stockOutOptions(),
        ]);
    }

    public function store(Request $request, StockOutService $service): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_outs', 'reference')],
            'destination' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'issued_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ], [
            'reference.unique' => 'Nomor referensi ini sudah pernah digunakan.',
            'items.required' => 'Tambahkan minimal satu produk yang dikeluarkan.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih dari nol.',
        ]);

        $issue = $service->issue($data);
        return redirect()->route('inventory.stock-out.show', $issue)->with('status', 'Stock Out berhasil dicatat dan saldo sudah diperbarui.');
    }

    public function show(StockOut $stockOut): View
    {
        return view('inventory.stock-out.show', ['issue' => $stockOut->load(['location', 'issuer', 'items.product.unit', 'items.batch.stockIn'])]);
    }
}
