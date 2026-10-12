<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Services\StockInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $locationId = $user->hasRole('pic_outlet')
            ? $user->location_id
            : ($request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id'));
        $query = StockIn::with(['supplier', 'location', 'receiver', 'items.product.unit']);

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('source', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
            });
        }

        if ($locationId) $query->where('location_id', $locationId);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->input('date_to'));
        }

        $receipts = $query->latest('received_at')->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total_receipts' => StockIn::when($locationId, fn ($q) => $q->where('location_id', $locationId))->count(),
            'total_items_qty' => (float) \App\Models\StockInItem::whereHas('stockIn', fn ($q) => $q->when($locationId, fn ($sub) => $sub->where('location_id', $locationId)))->sum('quantity'),
            'this_month_count' => StockIn::whereMonth('received_at', now()->month)->whereYear('received_at', now()->year)->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count(),
            'suppliers_count' => Supplier::where('is_active', true)->count(),
        ];

        return view('inventory.stock-in.index', [
            'receipts' => $receipts,
            'stats' => $stats,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'filters' => array_merge($request->only(['q', 'supplier_id', 'date_from', 'date_to']), ['location_id' => $locationId]),
        ]);
    }

    public function create(Request $request): View
    {
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('inventory.stock-in.create', [
            'products' => $products,
            'productOptions' => $products
                ->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'selectedLocationId' => $request->user()->hasRole('pic_outlet') ? $request->user()->location_id : $request->session()->get('selected_location_id'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockInService $service): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_ins', 'reference')],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'source' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_at'],
        ], [
            'reference.unique' => 'Nomor referensi ini sudah pernah digunakan.',
            'items.required' => 'Tambahkan minimal satu produk yang diterima.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih dari nol.',
        ]);

        $receipt = $service->receive($data);

        return redirect()->route('inventory.stock-in.show', $receipt)->with('status', 'Stock In berhasil dicatat dan saldo sudah diperbarui.');
    }

    public function show(StockIn $stockIn): View
    {
        return view('inventory.stock-in.show', ['receipt' => $stockIn->load(['supplier', 'location', 'receiver', 'items.product.unit'])]);
    }
}
