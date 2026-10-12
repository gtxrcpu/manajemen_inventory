<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockInItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $term = trim((string) ($data['q'] ?? ''));
        $user = $request->user();
        abort_unless(! $user->hasRole('pic_outlet') || $user->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        $locationId = $user->hasRole('pic_outlet')
            ? $user->location_id
            : $request->session()->get('selected_location_id');
        $like = '%'.$term.'%';

        $products = Product::query()->where('is_active', true)
            ->when($term !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', $like)->orWhere('sku', 'like', $like)))
            ->when($locationId, fn ($query) => $query->whereHas('stockMovements', fn ($movement) => $movement->where('location_id', $locationId)))
            ->with(['unit', 'category'])->orderBy('name')->limit(8)->get();

        $batches = StockInItem::query()->with(['product.unit', 'stockIn.location'])
            ->when($term !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('batch_number', 'like', $like)
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', $like)->orWhere('sku', 'like', $like))))
            ->whereHas('stockIn', fn ($receipt) => $receipt->when($locationId, fn ($q) => $q->where('location_id', $locationId)))
            ->latest()->limit(8)->get();

        $suppliers = Supplier::query()->where('is_active', true)
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
