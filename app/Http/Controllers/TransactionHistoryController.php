<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionHistoryController extends Controller
{
    private function historyQuery(Request $request): Builder
    {
        $request->validate([
            'type' => ['nullable', 'in:stock_in,stock_out,stock_opname,adjustment,audit'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $stockIn = DB::table('stock_ins')->selectRaw("id, 'stock_in' as type, reference, source as description, location_id, (SELECT name FROM locations WHERE locations.id = stock_ins.location_id) as location_name, received_by as user_id, (SELECT name FROM users WHERE users.id = stock_ins.received_by) as user_name, created_at, 'completed' as status")
            ->when($request->filled('product_id'), fn ($q) => $q->whereExists(fn ($items) => $items->selectRaw('1')->from('stock_in_items')->whereColumn('stock_in_items.stock_in_id', 'stock_ins.id')->where('stock_in_items.product_id', $request->integer('product_id'))));
        $stockOut = DB::table('stock_outs')->selectRaw("id, 'stock_out' as type, reference, destination as description, location_id, (SELECT name FROM locations WHERE locations.id = stock_outs.location_id) as location_name, issued_by as user_id, (SELECT name FROM users WHERE users.id = stock_outs.issued_by) as user_name, created_at, 'completed' as status")
            ->when($request->filled('product_id'), fn ($q) => $q->whereExists(fn ($items) => $items->selectRaw('1')->from('stock_out_items')->whereColumn('stock_out_items.stock_out_id', 'stock_outs.id')->where('stock_out_items.product_id', $request->integer('product_id'))));
        $opname = DB::table('stock_opnames')->selectRaw("id, 'stock_opname' as type, reference, period_type as description, location_id, (SELECT name FROM locations WHERE locations.id = stock_opnames.location_id) as location_name, counted_by as user_id, (SELECT name FROM users WHERE users.id = stock_opnames.counted_by) as user_name, created_at, status")
            ->when($request->filled('product_id'), fn ($q) => $q->whereExists(fn ($items) => $items->selectRaw('1')->from('stock_opname_items')->whereColumn('stock_opname_items.stock_opname_id', 'stock_opnames.id')->where('stock_opname_items.product_id', $request->integer('product_id'))));
        $adjustment = DB::table('stock_adjustments')->selectRaw("id, 'adjustment' as type, reference, reason as description, location_id, (SELECT name FROM locations WHERE locations.id = stock_adjustments.location_id) as location_name, created_by as user_id, (SELECT name FROM users WHERE users.id = stock_adjustments.created_by) as user_name, created_at, 'completed' as status")
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')));
        $concatRef = DB::connection()->getDriverName() === 'sqlite'
            ? "(subject_type || '#' || COALESCE(subject_id, '-'))"
            : "CONCAT(subject_type, '#', COALESCE(subject_id, '-'))";

        $audit = DB::table('administration_audit_logs')->selectRaw("id, 'audit' as type, {$concatRef} as reference, action as description, NULL as location_id, NULL as location_name, actor_id as user_id, (SELECT name FROM users WHERE users.id = administration_audit_logs.actor_id) as user_name, created_at, 'logged' as status")
            ->when($request->filled('product_id') || $request->filled('location_id') || ! $request->user()->hasPermissionTo('users.manage'), fn ($q) => $q->whereRaw('1 = 0'));

        $union = $stockIn->unionAll($stockOut)->unionAll($opname)->unionAll($adjustment)->unionAll($audit);
        $query = DB::query()->fromSub($union, 'history');

        if ($request->filled('type')) $query->where('type', $request->query('type'));
        $locationId = $request->user()->hasRole('pic_outlet')
            ? $request->user()->location_id
            : ($request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id'));
        if ($locationId) $query->where('location_id', $locationId);
        if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date('date_to'));
        if ($request->filled('reference')) {
            $term = '%'.trim($request->query('reference')).'%';
            $query->where(fn ($q) => $q->where('reference', 'like', $term)->orWhere('description', 'like', $term));
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManageUsers = $user && $user->hasPermissionTo('users.manage');
        $locationId = $user->hasRole('pic_outlet')
            ? $user->location_id
            : ($request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id'));
        $stockInCount = DB::table('stock_ins')->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count();
        $stockOutCount = DB::table('stock_outs')->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count();
        $opnameCount = DB::table('stock_opnames')->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count();
        $adjustmentCount = DB::table('stock_adjustments')->when($locationId, fn ($q) => $q->where('location_id', $locationId))->count();
        $auditCount = $canManageUsers && ! $locationId ? DB::table('administration_audit_logs')->count() : 0;

        $stats = [
            'total_all' => $stockInCount + $stockOutCount + $opnameCount + $adjustmentCount + $auditCount,
            'stock_in_count' => $stockInCount,
            'stock_out_count' => $stockOutCount,
            'opname_count' => $opnameCount,
            'adjustment_count' => $adjustmentCount,
            'audit_count' => $auditCount,
        ];

        return view('inventory.transaction-history.index', [
            'entries' => $this->historyQuery($request)->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => array_merge($request->only(['type', 'product_id', 'user_id', 'date_from', 'date_to', 'reference']), ['location_id' => $locationId]),
            'stats' => $stats,
        ]);
    }

    public function show(string $type, int $id): View
    {
        abort_unless(in_array($type, ['stock_in', 'stock_out', 'stock_opname', 'adjustment', 'audit'], true), 404);
        abort_if($type === 'audit' && ! auth()->user()->hasPermissionTo('users.manage'), 403);
        $entry = match ($type) {
            'stock_in' => DB::table('stock_ins')->where('id', $id)->first(),
            'stock_out' => DB::table('stock_outs')->where('id', $id)->first(),
            'stock_opname' => DB::table('stock_opnames')->where('id', $id)->first(),
            'adjustment' => DB::table('stock_adjustments')->where('id', $id)->first(),
            'audit' => DB::table('administration_audit_logs')->where('id', $id)->first(),
        };
        abort_if(! $entry, 404);

        $items = match ($type) {
            'stock_in' => DB::table('stock_in_items')->join('products', 'products.id', '=', 'stock_in_items.product_id')->where('stock_in_id', $id)->select('products.name as product', 'products.sku', 'stock_in_items.quantity', 'stock_in_items.batch_number', 'stock_in_items.expiry_date', DB::raw('NULL as physical_quantity'))->get(),
            'stock_out' => DB::table('stock_out_items')->join('products', 'products.id', '=', 'stock_out_items.product_id')->leftJoin('stock_in_items as batches', 'batches.id', '=', 'stock_out_items.batch_id')->where('stock_out_id', $id)->select('products.name as product', 'products.sku', 'stock_out_items.quantity', 'batches.batch_number', 'batches.expiry_date', DB::raw('NULL as physical_quantity'))->get(),
            'stock_opname' => DB::table('stock_opname_items')->join('products', 'products.id', '=', 'stock_opname_items.product_id')->where('stock_opname_id', $id)->select('products.name as product', 'products.sku', 'stock_opname_items.system_quantity as quantity', DB::raw('NULL as batch_number'), DB::raw('NULL as expiry_date'), 'stock_opname_items.physical_quantity')->get(),
            'adjustment' => DB::table('stock_adjustments')->join('products', 'products.id', '=', 'stock_adjustments.product_id')->where('stock_adjustments.id', $id)->select('products.name as product', 'products.sku', 'stock_adjustments.quantity', DB::raw('NULL as batch_number'), DB::raw('NULL as expiry_date'), DB::raw('NULL as physical_quantity'))->get(),
            default => collect(),
        };
        $location = isset($entry->location_id) ? Location::withTrashed()->find($entry->location_id) : null;
        $userId = match ($type) {
            'stock_in' => $entry->received_by ?? null,
            'stock_out' => $entry->issued_by ?? null,
            'stock_opname' => $entry->counted_by ?? null,
            'adjustment' => $entry->created_by ?? null,
            default => $entry->actor_id ?? null,
        };
        $actor = $userId ? User::withTrashed()->find($userId) : null;
        $reference = $entry->reference ?? ('AUDIT-'.$entry->id);
        $timestamp = $entry->created_at ?? null;
        $status = $entry->status ?? ($type === 'audit' ? 'logged' : 'completed');
        $description = $entry->description ?? $entry->action ?? $entry->reason ?? $entry->source ?? $entry->destination ?? $entry->period_type ?? null;
        $changes = isset($entry->changes) ? json_decode($entry->changes, true) : null;

        return view('inventory.transaction-history.show', compact('type', 'entry', 'items', 'location', 'actor', 'reference', 'timestamp', 'status', 'description', 'changes'));
    }
}
