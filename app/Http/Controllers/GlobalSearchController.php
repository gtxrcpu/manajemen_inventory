<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockInItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $term = trim((string) ($data['q'] ?? ''));
        $user = $request->user();
        $isPic = $user->isLocationScopedUser();
        $locationIds = $isPic ? $user->assignedLocationIds() : [];
        if ($isPic) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locationId = $isPic ? null : $request->session()->get('selected_location_id');
        $like = '%'.$term.'%';

        $products = Product::query()->where('is_active', true)
            ->when($term !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', $like)->orWhere('sku', 'like', $like)))
            ->when($isPic, fn ($query) => $query->whereHas('stockMovements', fn ($movement) => $movement->whereIn('location_id', $locationIds)))
            ->when($locationId, fn ($query) => $query->whereHas('stockMovements', fn ($movement) => $movement->where('location_id', $locationId)))
            ->with(['unit', 'category'])->orderBy('name')->limit(8)->get();

        $batches = StockInItem::query()->with(['product.unit', 'stockIn.location'])
            ->when($term !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('batch_number', 'like', $like)
                ->orWhereIn('product_id', $products->modelKeys())))
            ->whereHas('stockIn', fn ($receipt) => $receipt->when($isPic, fn ($q) => $q->whereIn('location_id', $locationIds))->when($locationId, fn ($q) => $q->where('location_id', $locationId)))
            ->latest()->limit(8)->get();

        $suppliers = Supplier::query()->where('is_active', true)
            ->when($isPic, fn ($query) => $query->whereIn('id', DB::table('stock_ins')->select('supplier_id')->whereIn('location_id', $locationIds)->whereNotNull('supplier_id')))
            ->when($term !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', $like)
                ->orWhere('contact_person', 'like', $like)->orWhere('phone', 'like', $like)))
            ->orderBy('name')->limit(8)->get();

        return view('search.index', [
            'term' => $term,
            'products' => $products,
            'batches' => $batches,
            'suppliers' => $suppliers,
            'selectedLocation' => $locationId ? Location::find($locationId) : null,
        ]);
    }
}
