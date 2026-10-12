<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        return view('inventory.stock-adjustment.index', ['adjustments' => StockAdjustment::with(['product.unit', 'location', 'creator'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        $balances = StockMovement::query()->selectRaw("product_id, location_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
            ->groupBy('product_id', 'location_id')->get()->groupBy('product_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [$row->location_id => (float) $row->balance]));

        return view('inventory.stock-adjustment.create', [
            'products' => $products,
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol, 'balances' => $balances[$product->id] ?? []])->values(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockAdjustmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_adjustments', 'reference')],
            'product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reference.unique' => 'Nomor referensi ini sudah pernah digunakan.',
            'quantity.gt' => 'Jumlah penyesuaian harus lebih dari nol.',
            'reason.required' => 'Alasan penyesuaian wajib diisi.',
        ]);
        $adjustment = $service->apply($data);
        return redirect()->route('inventory.stock-adjustments.show', $adjustment)->with('status', 'Penyesuaian stok berhasil dicatat.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        return view('inventory.stock-adjustment.show', ['adjustment' => $stockAdjustment->load(['product.unit', 'location', 'creator'])]);
    }
}
