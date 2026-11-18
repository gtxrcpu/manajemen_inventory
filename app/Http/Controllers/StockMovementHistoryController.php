<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockMovementHistoryController extends Controller
{
    private function query(Request $request): Builder|\Illuminate\Database\Eloquent\Builder
    {
        $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:stock_in,stock_out,stock_opname,adjustment'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $before = DB::table('stock_movements as prior')->selectRaw("COALESCE(SUM(CASE WHEN prior.type = 'in' THEN prior.quantity ELSE -prior.quantity END), 0)")
            ->whereColumn('prior.product_id', 'stock_movements.product_id')->whereColumn('prior.location_id', 'stock_movements.location_id')->whereColumn('prior.id', '<', 'stock_movements.id');
        $after = DB::table('stock_movements as prior')->selectRaw("COALESCE(SUM(CASE WHEN prior.type = 'in' THEN prior.quantity ELSE -prior.quantity END), 0)")
            ->whereColumn('prior.product_id', 'stock_movements.product_id')->whereColumn('prior.location_id', 'stock_movements.location_id')->whereColumn('prior.id', '<=', 'stock_movements.id');

        $query = StockMovement::query()->with([
            'product.unit', 'location', 'user', 'batch.stockIn', 'stockInItem.stockIn', 'stockOutItem.stockOut',
            'stockOpnameItem.opname', 'stockAdjustment',
        ])->addSelect(['before_quantity' => $before, 'after_quantity' => $after]);

        $user = $request->user();
        if ($user->isLocationScopedUser()) {
            $locationIds = $user->assignedLocationIds();
            abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
            if ($request->filled('location_id')) {
                abort_unless(in_array($request->integer('location_id'), $locationIds, true), 403);
                $query->where('location_id', $request->integer('location_id'));
            } else {
                $query->whereIn('location_id', $locationIds);
            }
        } else {
            $locationId = $request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id');
            if ($locationId) $query->where('location_id', $locationId);
        }

        if ($request->filled('product_id')) $query->where('product_id', $request->integer('product_id'));
        if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date('date_to'));
        if ($request->filled('type')) {
            match ($request->query('type')) {
                'stock_in' => $query->whereNotNull('stock_in_item_id'),
                'stock_out' => $query->whereNotNull('stock_out_item_id'),
                'stock_opname' => $query->whereNotNull('stock_opname_item_id'),
                'adjustment' => $query->whereNotNull('stock_adjustment_id'),
            };
        }
        if ($request->filled('reference')) {
            $reference = '%'.trim($request->query('reference')).'%';
            $query->where(function ($q) use ($reference) {
                $q->where('note', 'like', $reference)
                    ->orWhereHas('stockInItem.stockIn', fn ($receipt) => $receipt->where('reference', 'like', $reference))
                    ->orWhereHas('stockOutItem.stockOut', fn ($issue) => $issue->where('reference', 'like', $reference))
                    ->orWhereHas('stockOpnameItem.opname', fn ($opname) => $opname->where('reference', 'like', $reference))
                    ->orWhereHas('stockAdjustment', fn ($adjustment) => $adjustment->where('reference', 'like', $reference));
            });
        }
        return $query;
    }

    public function index(Request $request): View
    {
        return view('inventory.stock-movements.index', [
            'movements' => $this->query($request)->orderByDesc('id')->paginate(20)->withQueryString(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'locations' => Location::orderBy('name')->when($request->user()->isLocationScopedUser(), fn ($query) => $query->whereIn('id', $request->user()->assignedLocationIds()))->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['product_id', 'location_id', 'user_id', 'type', 'date_from', 'date_to', 'reference']),
        ]);
    }

    public function show(Request $request, StockMovement $stockMovement): View
    {
        $user = $request->user();
        if ($user->isLocationScopedUser()) {
            abort_unless(in_array((int) $stockMovement->location_id, $user->assignedLocationIds(), true), 403);
        }
        $movement = StockMovement::with(['product.unit', 'location', 'user', 'batch.stockIn', 'stockInItem.stockIn', 'stockOutItem.stockOut', 'stockOpnameItem.opname', 'stockAdjustment'])
            ->addSelect([
                'before_quantity' => DB::table('stock_movements as prior')->selectRaw("COALESCE(SUM(CASE WHEN prior.type = 'in' THEN prior.quantity ELSE -prior.quantity END), 0)")
                    ->whereColumn('prior.product_id', 'stock_movements.product_id')->whereColumn('prior.location_id', 'stock_movements.location_id')->whereColumn('prior.id', '<', 'stock_movements.id'),
                'after_quantity' => DB::table('stock_movements as prior')->selectRaw("COALESCE(SUM(CASE WHEN prior.type = 'in' THEN prior.quantity ELSE -prior.quantity END), 0)")
                    ->whereColumn('prior.product_id', 'stock_movements.product_id')->whereColumn('prior.location_id', 'stock_movements.location_id')->whereColumn('prior.id', '<=', 'stock_movements.id'),
            ])->findOrFail($stockMovement->id);
        return view('inventory.stock-movements.show', compact('movement'));
    }
}
